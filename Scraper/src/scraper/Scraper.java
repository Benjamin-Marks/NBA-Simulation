package scraper;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map.Entry;

import org.openqa.selenium.By;
import org.openqa.selenium.NoSuchElementException;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.phantomjs.*;

//TODO: we currently re-pull game and performance data from disk. We should just keep it in memory

public class Scraper {
	private final static String sep = File.separator;
	private final static String baseurl = "http://www.basketball-reference.com/players/";
	private final static String downloadDir = System.getProperty("user.dir") + sep + 
			"bin" + sep +  "downloads" + sep;
	private final static String startYear = "2015";
	private static PhantomJSDriver driver;

	public static void main(String[] args) {
		//Initialize PhantomJS path and start driver
		String phantomPath = System.getProperty("user.dir") + sep + "libs" + sep + 
				"phantomjs";
		System.setProperty("phantomjs.binary.path", phantomPath);
		driver = new PhantomJSDriver();

		String playerFile = "Players.csv";
		String gameFile = "Games.csv";
		String performanceFile = "Performance.csv";

		//Download database information
		try {
			System.out.println("Getting player information");
			getPlayers(playerFile);
			System.out.println("Retrieved player information");
			getPerformance(gameFile, performanceFile);
		} catch (Exception e) {
			e.printStackTrace();
		} finally {
			//If we don't quit the driver there will be zombie processes
			if (driver != null) {
				driver.quit();
			}
		}		
		System.out.println("Finding ID's");
		IDFind(gameFile, performanceFile);
		System.out.println("Terminating");
	}

	/**
	 * Helper method, write out String[]'s as CSV lines in String, return the string
	 * @param ID the playerID. This is not included in output if it is set to 0
	 * @param list the array of performance data
	 * @return the id and array formatted as a string
	 */
	private static String flushList(int ID, String[] list) {
		String theLine = "";
		if (ID != 0) {
			theLine += "" + ID + ",";
		}
		for (int i = 0; i < list.length - 1; i++) {
			theLine += list[i] + ",";
		}
		theLine += list[list.length - 1] + "\n";
		return theLine;
	}

	//gets performance data and places it in baseDownload _ path files
	private static void getPerformance(String gamePath, String performancePath) {
		//Record starting time for driver restarts
		long time = System.currentTimeMillis();

		//current player id
		int playerID = 0;

		//Create our output files
		File gameFile = new File(downloadDir + gamePath);
		File perfFile = new File(downloadDir + performancePath);
		FileWriter perfWriter = null;
		FileWriter gameWriter = null;
		try {
			perfFile.createNewFile();
			gameFile.createNewFile();
			gameWriter = new FileWriter(gameFile);
			gameWriter.write("date,homeTeam,awayTeam\n");
			perfWriter = new FileWriter(perfFile);
			perfWriter.write("playerID,gameID,teamName,ST,MIN,FG,FGA,3P,3PA,FT,FTA,ORB,DRB,AST,STL,BLK,TOV,PF\n");
		} catch (IOException e) {
			e.printStackTrace();
		}

		//Iterate through each starting letter of basketball-reference's database
		for (char curChar = 'a'; curChar <= 'z'; curChar++) {

			//Data structure to store the current list of games
			//The String array is three values: gameID, homeTeam, awayTeam
			HashMap<String, String[]> gameCSV = new HashMap<String, String[]>();

			//Load index page for all players with last name starting with curChar
			driver.get(baseurl + curChar);
			List<WebElement> playersMenu = null;
			try {
				playersMenu = driver.findElement(By.id("players")).findElement(
						By.tagName("tbody")).findElements(By.tagName("tr"));
			} catch (NoSuchElementException e) {
				//This character has no players, continue
				continue;
			}


			//Grab player's data from this page
			ArrayList<String[]> playerData = new ArrayList<String[]>();
			for (WebElement row: playersMenu) {
				List<WebElement> td = row.findElements(By.tagName("td"));
				//Determine if this is a player recent enough to track
				if (td.get(2).getText().compareTo(startYear) < 0)
					continue;
				//The first link is the one to take us to the player
				String link = row.findElement(By.tagName("a")).getAttribute("href");
				//Remove trailing '.html', add /gamelog/
				link = link.substring(0, link.length() - 5) + "/gamelog/";
				int startSeason = Integer.max(Integer.parseInt(td.get(1).getText()), Integer.parseInt(startYear));
				int endSeason = Integer.parseInt(td.get(2).getText());
				playerData.add(new String[] {link, String.valueOf(startSeason), String.valueOf(endSeason) });
			}

			for (String[] player : playerData) {
				//PhantomJSDriver has a known bug where it randomly "maybe" quits every 5 minutes
				//We restart every 4 minutes to prevent this
				if (System.currentTimeMillis() - time > 240000L) {
					driver.quit();
					driver = new PhantomJSDriver();
					time = System.currentTimeMillis();
				}
				playerID++;
				int startSeason = Integer.parseInt(player[1]);
				int endSeason = Integer.parseInt(player[2]);
				for (int i = startSeason; i <= endSeason; i++) {
					System.out.println("getPerformance:Loading " + player[0] + i);
					driver.get(player[0] + i);
					List<WebElement> csvMenu = driver.findElements(By.xpath("//*[@id=\"basic_div\"]/descendant::span[text()='CSV']"));
					if (csvMenu.size() == 0) {
						System.out.println("   No games for this season");
						continue;
					}
					csvMenu.get(0).click();
					String[] theGames = driver.findElement(By.id("csv_pgl_basic")).getText().split("\n");
					for (String game : theGames) {
						String[] items = game.split(",");
						//Check if this is a header column or if the player didn't play
						if (items[0].compareTo("999") > 0 || items[1].compareTo("") == 0) {
							continue;
						}
						/*
						 * Total Columns: 30 - We take the following 15
						 * 2: Date
						 * 4: Team
						 * 6: Opponent
						 * 8: Started Flag
						 * 9: Time Played
						 * 10: Field Goals
						 * 11: Field Goals Attempted
						 * 13: 3 Points
						 * 14: 3 Points Attempted
						 * 16: Free Throws
						 * 17: Free Throws Attempted
						 * 19: Offensive Rebounds
						 * 20: Defensive Rebounds
						 * 22: Assists
						 * 23: Steals
						 * 24: Blocks
						 * 25: Turnovers
						 * 26: Personal Fouls
						 */
						try {
							perfWriter.write(flushList(playerID, new String[] {items[2], items[4], items[8], items[9],
									items[10], items[11], items[13], items[14], items[16], items[17], items[19], 
									items[20], items[22], items[23], items[24], items[25], items[26]}));
						} catch (IOException e) {
							System.out.println("FLUSHLIST:PERF:ERROR");
							e.printStackTrace();
						}

						//Adjust game for consistent home/away formatting
						String[] newGame = null;
						if (items[5].compareTo("@") == 0) {
							newGame = new String[] {items[2], items[6], items[4]};
						} else {
							newGame = new String[] {items[2], items[4], items[6]}; 
						}
						//Add Game to list if not duplicate
						String key = newGame[0] + newGame[1] + newGame[2];
						if (!gameCSV.containsKey(key)) {
							gameCSV.put(key, newGame);
						}
					}
				}
			}
			try {
				//Write games
				for (Entry<String, String[]> game : gameCSV.entrySet()) {
					gameWriter.write(game.getValue()[0] + "," + 
							game.getValue()[1] + "," + 
							game.getValue()[2] + "\n");
				}
			} catch (IOException e) {
				e.printStackTrace();
			}
		}
		try {
			perfWriter.close();
			gameWriter.close();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}	

	//Gets players and places them in baseDownload+path file
	private static void getPlayers(String path) {

		FileWriter writer = null;
		try {
			new File(downloadDir).mkdir();
			File players = new File(downloadDir + path);
			players.getParentFile().mkdirs();
			players.createNewFile();
			writer = new FileWriter(players);
			writer.write("ID,Player,Pos,Birth Date\n");
		} catch (IOException e) {
			e.printStackTrace();
		}

		int playerID = 1;

		for (char curChar = 'a'; curChar <= 'z'; curChar++) {
			System.out.println("getPlayers:Getting Last Name " + curChar);
			driver.get(baseurl + curChar);
			try {
				WebElement csvMenu = driver.findElement(By.xpath("//*[@id=\"page_content\"]/descendant::span[text()='CSV']"));
				csvMenu.click();
			} catch (NoSuchElementException e) {
				//This letter has no data
				continue;
			}
			WebElement playerCopy = driver.findElement(By.id("csv_players"));
			String rawCSV = playerCopy.getText();

			//Convert this string to an array to do analysis
			String[] lines = rawCSV.split("\n");
			//Skip header row
			for (int i = 1; i < lines.length; i++) {
				String[] items = lines[i].split(",");
				//Check if player has current data
				if (items[2].compareTo(startYear) >= 0) {
					//output the variables we want
					try {
						writer.write("" + playerID + "," + items[0] + "," + items[3] + "," + items[6] + "\n");
						playerID++;
					} catch (IOException e) {
						System.out.println("ERROR PRINTING PLAYERS AT " + curChar);
						e.printStackTrace();
					}
				}
			}
		}

		try {
			writer.close();
		} catch (IOException e) {/*Ignore*/}

	}

	//Matches up all the various IDs
	private static void IDFind(String gamePath, String performancePath) {
		try {
			//Import games
			System.out.println("Importing Games");
			File gameF = new File(downloadDir + gamePath);

			//Load game data into memory
			HashMap<String, String[]> games = new HashMap<String, String[]>();
			BufferedReader gr = new BufferedReader(new FileReader(gameF));
			gr.readLine(); // skip header
			String line = gr.readLine();
			while (line != null) {
				String[] value = line.split(",");
				String key = value[0] + value[1] + value[2];
				games.put(key, line.split(","));
				line = gr.readLine();
			}
			gr.close();

			File outF = new File (downloadDir + "out" + performancePath);
			outF.createNewFile();
			FileWriter o = new FileWriter(outF);
			o.write("playerID,gameID,teamName,ST,MIN,FG,FGA,3P,3PA,FT,FTA,ORB,DRB,AST,STL,BLK,TOV,PF\n");

			File inFile = new File(downloadDir + performancePath);
			BufferedReader pr = new BufferedReader(new FileReader(inFile));
			pr.readLine(); //Skip Header
			System.out.println("Reading Performance");
			line = pr.readLine();
			while (line !=  null) {
				String[] splitLine = line.split(","); //1 = date, 2 = team
				String key = splitLine[1] + splitLine[2] + splitLine[3];
				if (games.containsKey(key)) {
					splitLine[1] = games.get(key)[0];
				} else {
					System.out.println("  ERROR: Did not find id for " + splitLine[1] + " " + splitLine[2]);				
				}
				String oLine = "";
				for(int i = 0; i < splitLine.length - 1; i++) {
					oLine += splitLine[i] + ","; 
				}
				oLine += splitLine[splitLine.length - 1] + "\n";
				o.write(oLine);
				line = pr.readLine();
			}
			pr.close();
			o.close();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}
}