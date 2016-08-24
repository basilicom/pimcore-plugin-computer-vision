ComputerVision Plugin
=============================

Developer info: [Pimcore at basilicom](http://basilicom.de/en/pimcore)

## Synopsis

This plugin adds a new button 'Computer Vision' in the asset detail view. By clicking this button the asset (in case
it's a folder - all child assets) gets analyzed by Microsoft Computer Vision API
(https://www.microsoft.com/cognitive-services/en-us/computer-vision-api) and the result is saved as Asset Metadata.

## Installation

Add the "basilicom-de/pimcore-plugin-computervision" repository to the composer.json in the toplevel directory of your pimcore installation.
Then enable and install the plugin in Pimcore Extension Manager (under Extras > Extensions)

Example:

    {
        "require": {
            "basilicom-pimcore-plugin/computervision": ">=1.0.0"
        }
    }

Installing the plugin a extension-computervision.xml is created in /website/var/config.

Create an Account for Microsoft Cognitive Services (https://www.microsoft.com/cognitive-services/), subscribe the
Computer Vision API and copy your authentication key to the extension-computervision.xml.


## License

GNU General Public License version 3 (GPLv3)