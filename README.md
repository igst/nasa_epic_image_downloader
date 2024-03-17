# NASA EPIC Image Download Service
## Service to download NASA EPIC Images

### Requirements 
 * PHP >=8.2

### Setup

#### 1. First, clone the repository
`git clone git@github.com:igst/nasa_epic_image_downloader.git`


#### 2. Go to project directory
`cd nasa_epic_image_downloader`


#### 3. Copy `.env.local.dist` to `.env.local`

`cp .env.local.dist .env.local`

#### 4. Add values for the environment variables

Example:


```
APP_ENV=dev
APP_SECRET=YOUR_APP_SECRET

NASA_EPIC_API_KEY=YOUR_NASA_API_KEY

#relative to the project directory
NASA_EPIC_IMAGE_STORAGE_DIRECTORY=var/storage
```

#### 5. Use the fetch command

Example 1 (provide a target directory and provide a specific date):

`symfony console nasa:epic some_target_directory 2024-02-16`

Example 2 (provide a target directory without a specific date. Latest available date will be used):

`symfony console nasa:epic some_target_directory 2024-02-16`

For more information, use `symfony console help nasa:epic`