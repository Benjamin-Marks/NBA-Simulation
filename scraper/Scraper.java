package downloader;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;

import org.openqa.selenium.By;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.phantomjs.*;


public class Scraper {

	private final static String baseurl = "http://www.basketball-reference.com/players/";
	private final static String baseDownload = System.getProperty("user.home") + "/421db_csv/";
	private final static String startYear = "2005";
	private final static String curYear = "2015";
	private static PhantomJSDriver driver;
	private static int gameID = 0;

	public static void main(String[] args) {
		System.out.println("Web Scraper for G9Database - CS 421. Written by Ben Marks");
		System.out.println("This will only run on Unix systems - phantomjs binary exist in  user's home directory");
		System.out.println("Binary can be obtained at: https://code.google.com/p/phantomjs/downloads/list");
		File file = new File(System.getProperty("user.home") + "/phantomjs");
		System.setProperty("phantomjs.binary.path", file.getAbsolutePath());
		driver = new PhantomJSDriver();
		System.out.println("Getting player information");
		getPlayers("players.csv");
		System.out.println("Retrieved player information");
		getPerformance();
		driver.quit();
		System.out.println("Finding ID's");
		IDFind();
		System.out.println("Merging Games");
		mergeGames();
		System.out.println("Terminating");
	}

	private static void getPerformance() {
		long time = System.nanoTime();
		int playerID = 0;
		for (char curChar = 'a'; curChar <= 'z'; curChar++) {
			driver.quit();
			driver = new PhantomJSDriver();
			System.out.println("getPerformance: last name " + curChar);
			if (curChar == 'x')//No x players TODO: maybe make this robust at some point
				continue;
			
			ArrayList<String[]> gameCSV = new ArrayList<String[]>(); //ID, homeTeam, awayTeam
			FileWriter writer = null;
			FileWriter gamew = null;
			try {
				File gameFile = new File(baseDownload + curChar + "Games.csv");
				gameFile.createNewFile();
				gamew = new FileWriter(gameFile);
				gamew.write("date,homeTeam,awayTeam\n");
				File perfFile = new File(baseDownload + "performance/" + curChar + ".csv");
				perfFile.getParentFile().mkdirs();
				perfFile.createNewFile();
				writer = new FileWriter(perfFile);
				writer.write("playerID,gameID,teamName,ST,MIN,FG,FGA,3P,3PA,FT,FTA,ORB,DRB,AST,STL,BLK,TOV,PF\n");
			} catch (IOException e) {
				e.printStackTrace();
			}

			driver.get(baseurl + curChar);
			List<WebElement> playersMenu = driver.findElement(By.id("players"))
											.findElement(By.tagName("tbody")).findElements(By.tagName("tr"));
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
				playerData.add(new String[] {link, "" + startSeason, "" + endSeason });
			}
			
			for (String[] player : playerData) {
				//PhantomJSDriver has a known bug where it randomly "maybe" quits every 5 minutes
				//We restart every 4 minutes to prevent this
				if (System.nanoTime() - time > 4L * 60000000000L) {
					driver.quit();
					driver = new PhantomJSDriver();
				}
				playerID++;
				int startSeason = Integer.parseInt(player[1]);
				int endSeason = Integer.parseInt(player[2]);
				for (int i = startSeason; i <= endSeason; i++) {
					System.out.println("going to " + player[0] + i);
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
						//Total Columns: 30
						//We want: 3, 5, 9, 10, 11, 12, 14, 15, 17, 18, 19, 20, 22, 26, 27 - 15 total
						try {
						writer.write(flushList(playerID, new String[] {items[2], items[4], items[8], items[9],
								items[10], items[11], items[13], items[14], items[16], items[17], items[19], 
								items[20], items[22], items[23], items[24], items[25], items[26]}));
						} catch (IOException e) {
							System.out.println("FLUSHLIST:PERF:ERROR");
							e.printStackTrace();
						}
						//Add Game to list if not duplicate
						addGame(gameCSV, new String[] {items[2], items[4], items[5], items[6]});
					}
				}
			}
			try {
				writer.close();
				//Write games
				for (String[] game : gameCSV) {
					gamew.write(game[0] + "," + game[1] + "," + game[2] + "\n");
				}
				gamew.close();
			} catch (IOException e) {/*Ignore*/}
		}
		System.out.println("getPerformance:printing Games");
	}

	//Helper method, write out String[]'s as CSV lines in String, return the string
	//ignores ID if set to 0
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

	//Helper method, searches theGames for game and adds it if it doesn't exist
	private static void addGame(ArrayList<String[]> games, String[] newGame) {
		if (newGame[2].compareTo("@") == 0) {
			String t = newGame[1];
			newGame[1] = newGame[3];
			newGame[3] = t;
		}
		boolean addGame = true;
		for (String[] game : games) {
			//if item is duplicate, break
			if (game[0].compareTo(newGame[0]) == 0 && game[1].compareTo(newGame[1]) == 0 
					&& game[2].compareTo(newGame[3]) == 0) {
				addGame = false;
				break;
			}
		}
		if (addGame) {
			games.add(new String[] {newGame[0], newGame[1], newGame[3]});
		}
	}

	//Gets players and places them in baseDownload+path file
	private static void getPlayers(String path) {

		FileWriter writer = null;
		try {
			new File(baseDownload).mkdir();
			File players = new File(baseDownload + path);
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
			if (curChar == 'x') { //No x players TODO at some point maybe make that robust
				continue;
			}
			driver.get(baseurl + curChar);
			WebElement csvMenu = driver.findElement(By.xpath("//*[@id=\"page_content\"]/descendant::span[text()='CSV']"));
			csvMenu.click();
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

	private static void IDFind() {
		try {
			//Import games
			System.out.println("Importing Games");
			String[][] games = new String[13291][4];
			int curG = 0;
			File gameF = new File(baseDownload + "games.csv");
			BufferedReader gr = new BufferedReader(new FileReader(gameF));
			gr.readLine(); // skip header
			String line = gr.readLine();
			while (line != null) {
				games[curG] = line.split(",");
				curG++;
				line = gr.readLine();
			}
			gr.close();
			File outF = new File (baseDownload + "performance.csv");
			outF.createNewFile();
			FileWriter o = new FileWriter(outF);
			o.write("playerID,gameID,teamName,ST,MIN,FG,FGA,3P,3PA,FT,FTA,ORB,DRB,AST,STL,BLK,TOV,PF\n");
			
			for (char curChar = 'a'; curChar <= 'z'; curChar++) {
				System.out.println("Reading Performance " + curChar);
				if (curChar == 'x') {
					continue;
				}
				File perfF = new File(baseDownload + "performance/" + curChar + ".csv");
				BufferedReader pr = new BufferedReader(new FileReader(perfF));
				pr.readLine(); //Skip Header
				line = pr.readLine();
				while (line !=  null) {
					String[] splitLine = line.split(",");
					//1 = date, 2 = team
					boolean foundGame = false;
					for (String[] game : games) {
						if (splitLine[1].compareTo(game[1]) == 0 
								&& (splitLine[2].compareTo(game[2]) == 0 || splitLine[2].compareTo(game[3]) == 0)) {
							foundGame = true;
							splitLine[1] = game[0];
							break;
						}
					}
					if (foundGame == false) {
						System.out.println("  ERROR: Did not find id for " + splitLine[1] + " " + splitLine[2]);
						//break;
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
			}
			o.close();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}
	
	private static void mergeGames() {
		ArrayList<String[]> games = new ArrayList<String[]> ();
		char curChar = 'a';
		for (curChar = 'a'; curChar <= 'z'; curChar++) {
			System.out.println("Combining Games: " + curChar);
			if (curChar =='x') {
				continue;
			}
			File curFile = new File(baseDownload + curChar + "Games.csv");
			BufferedReader r = null;
			try {
				r = new BufferedReader(new FileReader(curFile));
				r.readLine(); //skip header
				String line = r.readLine();
				while(line != null) {
					String[] in = line.split(",");
					boolean addGame = true;
					for (String[] game : games) {
						if (in[0].compareTo(game[1]) == 0 && in[1].compareTo(game[2]) == 0 
								&& in[2].compareTo(game[3]) == 0) {
							addGame = false;
							break;
						}
					}
					if (addGame) {
						gameID++;
						games.add(new String[] {"" + gameID, in[0], in[1], in[2]});
					}
					line = r.readLine();
				}
				r.close();
			} catch (IOException e) {
				e.printStackTrace();
				break;
			}
		}
		System.out.println("Found files up to " + curChar);
		File outFile = new File(baseDownload + "/mergeGame/a-" + (curChar - 1) + ".csv");
		try {
			FileWriter o = new FileWriter(outFile);
			o.write("gameID,date,homeTeam,awayTeam\n");
			for (String[] game : games) {
				o.write(game[0] + "," + game[1] + "," + game[2] + "," + game[3] + "\n");
			}
			o.close();
		} catch (IOException e) {
			e.printStackTrace();
		}
	}
}