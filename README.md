# ClaroBi

## About

Allows [ClaroBi][clarobi] to gather information from Products, Stocks, Orders, Invoices, Customers, Abandoned Carts.  
This module is tested for the following versions:
* WordPress versions: 5.3.x
* WooCommerce versions: 3.9.0 - 4.0.1


## Module version guide

| WordPress version | WooCommerce version | Module version |  Repo                | Doc                |  PHP Version |
|-------------------|---------------------|----------------|---------------------|---------------------|-------------|
| 5.3.x             | 3.9.0 - 4.0.1       | 1.0.0          |  [release/1.0.0][clarobi-repo] | |   7.3.x or greater    |

## Requirements

1. PHP version  7.3.x
2. WooCommerce plugin installed and activated.

## Installation

To install module on WordPress, you can get it from WordPress market place or from out *GitHub* repository.


### Install from GitHub

To install from repository you can either download the zip package or clone the repository.
You can find it at [ClaroBi module repo][clarobi-repo].

#### Download zip package
 Once you access our repo, click *Clone or Download* and choose *Download ZIP*.
 After the download is complete, rename module folder to `clarobi`.  
 Now, you can either:
 1. unzip the folder and place it under wordpress_folder_root >> wp-content >> plugins, if you have access to project  
 or
 2. go to *WordPress Admin >> Plugins >> Add new* and click *Upload plugin* and select the path to the module zip folder
 
#### Clone repository

To clone the repo follow the steps:
* open *GitBash* console and go to *plugins* folder in your WordPress project
* run the command: `git clone git@github.com:intelive/clarobi-woocommerce.git clarobi`
    * Note: the name of the folder in which the repository will be clone must be `clarobi`

## Configuration

To configure the module you need to have an account on our website.  
> If you do not have one, please access [ClaroBi][clarobi] and start your 6 months free trial.  
After you have successfully registered you will receive from ClariBi 2 keys necessary for authentication   
>and data encryption ( `API KEY` and `API SECRET` ) and your `LICENSE KEY`.

After you have all the necessary keys, please follow the steps:      
* In the configuration form, you will need to provide all the data as follows:
    * `Domain`: your shop domain (same as the one provided for registration on our website)
    * `License` key: license key provided by ClaroBi
    * `Api key`: Api key provided by ClaroBi
    * `Api secret`: Api secret provided by ClaroBi
* After all the inputs have been completed, click *Save*.

> You may come back ( *WordPress Admin >> Setting >> ClaroBi*) at any time to finish the configuration,  
> but no analytics will be run until everything is setup.    


## Statistics

After the installation the module will start calculate and gather data for analytics.   
All the information retrieved from your shop can be found by accessing you [ClaroBi account][clarobi-login].

## Uninstall

To uninstall ClaroBi module you need to:
 * Locate it in *WordPress Admin >> Plugins >> Installed Plugins*
 * Click *Deactivate* to stop the module from working.
 * And you may completely uninstall the plugin and delete it.


[clarobi]: https://clarobi.com/
[clarobi-login]: https://app.clarobi.com/login
[clarobi-repo]:  https://github.com/intelive/clarobi-woocommerce
