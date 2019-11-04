<?php

include "classes/Repository.php";

echo "<pre>";

// works - proj on root
// $repo = new Repository( "/facebook/flipper" );
// $repo = new Repository( "/facebook/yoga" );
// $repo = new Repository( "/android/uamp" );
$repo = new Repository( "/android/plaid" );

// works with no result - proj with folder
// $repo = new Repository( "/android/storage-samples", "ActionOpenDocument" );
// $repo = new Repository( "/android/app-bundle-samples", "DynamicCodeLoadingKotlin" );
// $repo = new Repository( "/android/user-interface-samples", "AdvancedImmersiveMode" );
// $repo = new Repository( "/android/user-interface-samples", "ElevationBasic" );
// $repo = new Repository( "/android/user-interface-samples", "Notifications" );

// @TODO external config file
// $repo = new Repository( "/facebook/facebook-android-sdk" );
// $repo = new Repository( "/facebook/screenshot-tests-for-android" );


