# Reviews.co.uk Prestashop Plugin

## Installation

- Download plugin from the plugin repository to local directory as zip.
- Unzip locally downloaded file
- Create zip archive of reviewscouk directory or run `bash build.sh`
- Go to the PrestaShop administration page [http://your-prestashop-url/admin].
- Go to Modules > Modules.
- Click add new module and select the archive you've just created
- Activate and configure the plugin


# Setting Up Local PrestaShop Store

## 1. Download PrestaShop

- Go to - [https://github.com/PrestaShop/PrestaShop/releases](https://github.com/PrestaShop/PrestaShop/releases)
- Download the specific development release version of PrestaShop (usually labelled as “Source code (zip)”)
- Unzip the download and move the folder to the directory you store your projects in


## 2. Configure DDEV

- Open your terminal and navigate to the project folder
- Create a new ddev project in folder using apache webserver
- Run `$ ddev config –-webserver-type=apache-fpm`


## 3. Download dependancies
#### PHP Dependencies
Use [composer](https://getcomposer.org/download/) to download the project’s dependencies:

```bash
ddev composer install
```

#### JavaScript and CSS dependencies
PrestaShop uses `NPM` to manage dependencies and Webpack to compile them into static assets. You only need `NodeJS 14.x` (version `16.x` is recommended).

```bash
nvm use 14
```

```bash
cd /path/to/prestashop
make assets
```


## 4. Setting up file permissions
If you do not have some of the folders above, please create them before changing permissions. For example:

```bash
mkdir log var/logs admin-dev/autoupgrade
```

You can set up the appropriate permissions using this command:

```bash
chmod -R +w admin-dev/autoupgrade app/config var/logs cache config download img log mails modules override themes translations upload var
```


## 5. Installation & Setup

- Follow installation instructions
- You can use `$ ddev describe` to get information about the database access credentials for use inside the container


## 7. Setting up default image types for the front office
Prestashop uses image thumbnails generated from products that are used in the front office.
By default, these thumbnails are not generated. There are a few steps to take to initiate the thumbnail generation.

1. Design -> Image Settings -> Image Type
	-  Set up - home_default, large_default, medium_default, small_default - types

2. International -> Localization -> Languages -> {Language} -> No Picture Image
	-  Set a default image

3. International -> Localization -> Configuration
	-  Ensure the correct language is selected

5. Design -> Image Settings -> Regenerate thumbnails
	-  Click regenerate

## 7. Ensure debug mode is turned off
If there are no major errors and issues, disable debug mode to show the front office pages

-  PrestaShop Dashboard -> Advanced Parameters -> Performance -> Debug mode Panel -> Set it to No
