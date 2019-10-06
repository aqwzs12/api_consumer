# api_consumer


# Install the module
No dependencies needed .
You can use drush or go to extends and install.

# How it works
This module retrieve data from the Pokemon API.
To make an import you have two options : 
  - By Running cron 
  - By running a batch [ Config -> Import Pokemon ]
  ![alt text](https://raw.githubusercontent.com/aqwzs12/api_consumer/master/screencapture-running-batch.png)

# To display Data 
   - It will be more intersting to have an bootstrap theme installed the pokemon full mode display is based on same class of it .
 ( You can see attached to this repo some screen shot of what it looks like when you're on bootstrap )
   - '/pokemons' view that display all retrieved pokemons .
   - '/pokemons-gen-1' view that display only pokemon of the first generation with some filters exposed to user
   
   ![alt text](https://raw.githubusercontent.com/aqwzs12/api_consumer/master/screencapture-listing-pokemons.png)
   ![alt text](https://raw.githubusercontent.com/aqwzs12/api_consumer/master/screencapture-localhost-monster-detail.png)
