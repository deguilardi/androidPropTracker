Android project prop tracker
===================
This project tracks changes of a gradle config property of Android projects on github. You can track every change of the properties minSdkVersion, compileSdkVersion and targetSdkVersion.

## Requirements
  - **apache2**: you must install apache2
  - **php**: version > 5.4

## Install
  - Clone this repository;
  - Make it accessible by your apache.

## Run
  - Just access the page on your favorite browser
  - Fill up the form
  ![Form Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/form.png)
  - Analyse the results
  ![Results Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/results.png)

## How it works
  ![Modules diagrams Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/modules-diagrams.png)

## Limitations
  - There are currently only 3 properties that can be tracked: minSdkVersion, compileSdkVersion and targetSdkVersion. There are plans to make it work for any gradle property.