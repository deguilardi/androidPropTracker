<?php
define( 'GIT_URL_BASE', 'https://github.com' );
define( 'GIT_RAW_CODE_URL_BASE', 'https://raw.githubusercontent.com' );
define( 'GIT_BRANCH_DEFAULT', 'master' );

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
	"128,0,0",
	"0,128,0",
	"0,0,128",
	"128,128,0",
	"128,0,128",
	"0,128,128"
);