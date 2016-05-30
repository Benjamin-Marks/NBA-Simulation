# NBA-Simulation
NBA Season Simulation Database

A project designed to simulate an NBA season. There are two componenets, a scraper and database application. 

**Scraper**

* Web Scraper written in Java using the Selenium framework and a PhantomJS browser
* Downloads player and game data from basketball-reference.com
* Exports scraped data into .csv files for easy importing into a database

**Web App**

* Web Interface written in HTML and PHP
* Displays player rankings based on [Expected Wins Produced](http://wagesofwins.com/how-to-calculate-wins-produced/)
* Simulates a season and displays team win-loss records as well as the scores of individual games
* Allows you to adjust team composition, shift players to different teams and observe the effect
  * What would happen to the Heat if Lebron stayed in Miami?
  * What if Steph Currey came to Cleveland?
  * What if every single starter left the Lakers?
