<?php
define( 'GIT_URL_BASE', 'https://github.com' );
define( 'GIT_RAW_CODE_URL_BASE', 'https://raw.githubusercontent.com' );
define( 'GIT_BRANCH_DEFAULT', 'master' );
define( 'ENABLE_CACHE', true );
define( 'ENABLE_CACHE_RESULTS', false );
define( 'ENABLE_IGNORING_NAMES', true );

$reposOptions = array(

	// facebook
	new RepositoryEntity( "/facebook/device-year-class", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/facebook-android-sdk", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/flipper", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/fresco", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/screenshot-tests-for-android", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/shimmer-android", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/stetho", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/TextLayoutBuilder", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/facebook/yoga", GIT_BRANCH_DEFAULT, "" ),

	// google
	new RepositoryEntity( "/android/architecture-components-samples", GIT_BRANCH_DEFAULT, "BasicSample" ),
	new RepositoryEntity( "/android/architecture-components-samples", GIT_BRANCH_DEFAULT, "PersistenceContentProviderSample" ),
	new RepositoryEntity( "/android/architecture-components-samples", GIT_BRANCH_DEFAULT, "PersistenceMigrationsSample" ),
	new RepositoryEntity( "/android/architecture-components-samples", GIT_BRANCH_DEFAULT, "WorkManagerSample" ),
	new RepositoryEntity( "/android/architecture-samples", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/android/plaid", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/android/sunflower", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/android/uamp", GIT_BRANCH_DEFAULT, "" ),

	// others
	new RepositoryEntity( "/chrisbanes/tivi", GIT_BRANCH_DEFAULT, "" ),
	new RepositoryEntity( "/PierfrancescoSoffritti/android-youtube-player", GIT_BRANCH_DEFAULT, "" ), 
	new RepositoryEntity( "/DrKLO/Telegram", GIT_BRANCH_DEFAULT, "" ), 

	// work with no result
	// new RepositoryEntity( "/android/topeka", GIT_BRANCH_DEFAULT, "" ),
	// new RepositoryEntity( "/android/storage-samples", GIT_BRANCH_DEFAULT, "ActionOpenDocument" ),
	// new RepositoryEntity( "/android/app-bundle-samples", GIT_BRANCH_DEFAULT, "DynamicCodeLoadingKotlin" ),
	// new RepositoryEntity( "/android/user-interface-samples", GIT_BRANCH_DEFAULT, "AdvancedImmersiveMode" ),
	// new RepositoryEntity( "/android/user-interface-samples", GIT_BRANCH_DEFAULT, "ElevationBasic" ),
	// new RepositoryEntity( "/android/user-interface-samples", GIT_BRANCH_DEFAULT, "Notifications" ),
	// new RepositoryEntity( "/facebook/conceal", GIT_BRANCH_DEFAULT, "" ),
	// new RepositoryEntity( "/facebook/SoLoader", GIT_BRANCH_DEFAULT, "" ),
);

$resultGraphColors = array(
	"255,0,0",
	"0,255,0",
	"0,0,255",
	"255,255,0",
	"255,0,255",
	"0,255,255",

	"200,0,0",
	"0,200,0",
	"0,0,200",
	"200,200,0",
	"200,0,200",
	"0,200,200",

	"155,0,0",
	"0,155,0",
	"0,0,155",
	"155,155,0",
	"155,0,155",
	"0,155,155"
);

$resultHeatColors = array(
	"220,220,255",
	"190,190,255",
	"160,160,255",
	"130,130,255",
	"100,100,255"
);

$ignoredRepositoriesNames = array( 

    // known frameworks
    "react",
    // "frameworks_av", 
    // "flutter",
    // "reactjs",
    // "react-native",
    // "reactnative",
    // "kotlin",
    // "xamarin",
    // "cordova",

    // // educational and tests
    // "hello_world",
    // "hello-world",
    // "learning", 
    // "class",
    // "practice",
    // "test",
    // "sample",
    // "hackathon",
    // "research",
    // "course",
    // "udacity",
    // "development",

    // // core stuff
    // // "android_frameworks_base", "platform_frameworks_base", etc...
    // // "android_packages_apps_", "platform_packages_apps_", etc...
    // "frameworks_base",
    // "fameworks_base",
    // "framework_base",
    // "frameworks-base",
    // "frameworks_support",
    // "frameworks-support",
    // "framework_support",
    // "frameworks_rs",
    // "packages_apps_",
    // "packages-apps-",
    // "platform-frameworks",
    // "platform_frameworks",
    // "android_platform",
    // "android_frameworks",
    // "android_packages",
    // "platform_packages",
    // "packages_inputmethods",
    // "platform_art", "platform-art",
    // "android_art",
    // "selinux",
    // "signal-android",
    // "signal_android",
    // "signalandroid",
    // "platform_libcore",
    // "androidaccessibility",
    // "android-accessibility",

    // // the core Android SELinux policy configuration
    // "system_sepolicy",
    // "system-sepolicy",

    // // an operating system
    // "fuchsia",

    // // Skia is a complete 2D graphic library for drawing Text
    // "skia",
    // "skqp",

    // // investigate
    // // /Blaze-AOSP/system_netd
    // "system_netd",
    // "godot",
    // "opencamera",
    // "tensorflow"
);