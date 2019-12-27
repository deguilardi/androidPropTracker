Android project prop tracker
===================
This project tracks changes of a gradle config property of Android projects on github. You can track every change of the properties minSdkVersion, compileSdkVersion and targetSdkVersion.

## Requirements
  - **apache2**: you must install apache2
  - **php**: version > 5.4

## Install
  - Clone this repository;
  - Make it accessible by your apache.
  - change php.ini `max_input_vars = 1000000000`

## Run
  - Just access the page on your favorite browser
  - Fill up the form
  ![Form Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/form.png)
  - Analyse the results
  ![Results Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/results-graph.png)
  ![Results Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/results-table-1.png)
  ![Results Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/results-chart-1.png)
  ![Results Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/results-table-2.png)
  ![Results Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/results-chart-2.1.png)
  ![Results Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/results-chart-2.2.png)

## How it works
  ![Flow diagram Image](https://raw.githubusercontent.com/deguilardi/android_project_prop_tracker/master/assets/github/flow.png)

## Limitations
  - There are currently only 3 properties that can be tracked: minSdkVersion, compileSdkVersion and targetSdkVersion. There are plans to make it work for any gradle property.