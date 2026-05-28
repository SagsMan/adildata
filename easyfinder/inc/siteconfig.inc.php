<?php
	$settings = [];
	if($settings = $site_settings->SiteProperty()){
	foreach ($settings as $setting) {
	$settings[$setting->setting_key] = $setting->setting_value;
		}
	}
	//define('SITE_STATUS', $settings['site_status']);
	define('SITE_URL', $settings['website_url']);
	define('SITE_TITLE', $settings['website_title']);
	define('SITE_LOGO', $settings['site_logo']);
	define('SITE_LOGO_2', $settings['site_logo_2']);
	define('CURL_SSL_VERIFY', false);
	define('ABOUT_US', $settings['about_us']);
	define('CONTACT_US', $settings['contact_us']);
	define('DSMS_INSTALL_INSTRUCTION', $settings['dsms_install_instruction']);
	define('HOW_USE_DSMS', $settings['dsms_feature_details']);
	define('TOP_BANNER_TEXT', $settings['top_banner_text']);
	define('BOTTOM_BANNER_TEXT', $settings['bottom_banner_text']);
	define('ABOUT_US_HEADER', $settings['about_us_header']);
	define('WHY_PEOPLE_CHOOSE_US_HEADER', $settings['why_people_choose_us_header']);
	define('WHY_PEOPLE_CHOOSE_US_TAP_ONE', $settings['why_people_choose_us_tap_one']);
	define('WHY_PEOPLE_CHOOSE_US_TAP_TWO', $settings['why_people_choose_us_tap_two']);
	define('WHY_PEOPLE_CHOOSE_US_TAP_THREE', $settings['why_people_choose_us_tap_three']);
	define('UI_DEVICE_ONE', $settings['ui_device_one']);
	define('UI_DEVICE_TWO', $settings['ui_device_two']);
	define('UI_DEVICE_THREE', $settings['ui_device_three']);
	define('UI_DEVICE_FOUR', $settings['ui_device_four']);
	define('INSTALL_DSMS_YOUTUBE', $settings['install_dsms_youtube']);
	define('PAYSTACK_API', $settings['paystack_api']);
	define('VTPASS_USERNAME', $settings['vtpass_username']);
	define('VTPASS_PASSWORD', $settings['vtpass_password']);
	define('VTPASS_LINK', $settings['vtpass_link']);
	define('VTPASS_AUTH', base64_encode(VTPASS_USERNAME.":".VTPASS_PASSWORD));
	define('COMPANY_CAC_PRICE', $settings['company_cac_price']);
	define('BUSINESS_CAC_PRICE', $settings['business_cac_price']);
	define('BVN_VERIFICATION_PRICE', $settings['bvn_verification_price']);
	define('BVN_ADVANCE_VERIFICATION_PRICE', $settings['bvn_advance_verification_price']);
	define('NIN_VALIDATION_PRICE', $settings['nin_validation_price']);
	define('NIN_PERSONALIZATION_PRICE', $settings['nin_personalization_price']);
	define('IPE_CLEARING_PRICE', $settings['ipe_clearing_price']);
	define('NIN_NAME_MODIFICATION_PRICE', $settings['nin_name_modification_price']);
	define('NIN_DOB_MODIFICATION_PRICE', $settings['nin_dob_modification_price']);
	define('DOJA_BASE_URL', $settings['DOJA_BASE_URL']);
	define('DOJA_API_KEY', $settings['DOJA_API_KEY']);
	define('DOJA_APP_ID', $settings['DOJA_APP_ID']);
	define('NIN_SEARCH_BASIC_PRICE', $settings['nin_search_basic_price']);
	define('NIN_SEARCH_REGULAR_PRICE', $settings['nin_search_regular_price']);
	define('SEAMFIX_BASE_URL', $settings['SEAMFIX_BASE_URL']);
	define('SEAMFIX_API_KEY', $settings['SEAMFIX_API_KEY']);
	define('SEAMFIX_USER_ID', $settings['SEAMFIX_USER_ID']);
	define('NIN_SEARCH_IMPROVE_PRICE', $settings['nin_search_improve_price']);
	define('VIRTUAL_NIN_SEARCH_PRICE', $settings['virtual_nin_search_price']);
	define('MONNIFY_BASE_URL', $settings['MONNIFY_BASE_URL']);
	define('MONNIFY_API_KEY', $settings['MONNIFY_API_KEY']);
	define('MONNIFY_API_SECRET', $settings['MONNIFY_API_SECRET']);
	define('MONNIFY_API_CONTRACT', $settings['MONNIFY_API_CONTRACT']);
	
	// ─── Credentials (define once, use everywhere) ───────────────────────────────
    define('RELOADLY_CLIENT_ID',     'M0GVocnJSKfJjkQ1CdQHMNyKug92ejbS');
    define('RELOADLY_CLIENT_SECRET', 'CmxPdndqLZ-UYu2R3Kd0ug7XM1o94k-yHaOwnc2ODH6DZ69vP7tEyBo3M2Fa1sk');
    define('RELOADLY_AUDIENCE',      'https://topups.reloadly.com');
    define('RELOADLY_TOKEN_URL',     'https://auth.reloadly.com/oauth/token');
    
    // ─── Reusable token fetch function ───────────────────────────────────────────
    function fetchReloadlyToken($site_settings) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => RELOADLY_TOKEN_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,           
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => CURL_SSL_VERIFY,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode([  
                "client_id"     => RELOADLY_CLIENT_ID,
                "client_secret" => RELOADLY_CLIENT_SECRET,
                "grant_type"    => "client_credentials",
                "audience"      => RELOADLY_AUDIENCE,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
    
        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);
    
        if ($err) {
            error_log("Reloadly token fetch cURL error: $err");
            return null;
        }
    
        $res = json_decode($response, true);
    
        if (!isset($res["access_token"])) {
            error_log("Reloadly token fetch failed: " . $response);
            return null;
        }
    
        $res["issued_at"] = time();
        $site_settings->insertUpdateProperty('reloaddly_api', json_encode($res)); 
        return $res["access_token"];
    }
    
    // ─── Main logic ──────────────────────────────────────────────────────────────
    $reloadly_key = "";
    $buffer       = 300; // refresh 5 mins before expiry
    
    $stored = $settings['reloaddly_api'] ?? ''; 
    
    if (empty($stored) || $stored === '{}') {
        // No token stored yet — fetch fresh
        $reloadly_key = fetchReloadlyToken($site_settings);
    
    } else {
        $array      = json_decode($stored, true);
        $expiryTime = ($array["issued_at"] ?? 0) + ($array["expires_in"] ?? 0);
    
        if (time() >= ($expiryTime - $buffer)) {
            // Token expired (or about to) — refresh
            $reloadly_key = fetchReloadlyToken($site_settings);
        } else {
            // Token still valid
            $reloadly_key = $array["access_token"];
        }
    }
    
    // Catch empty key before defining
    if (empty($reloadly_key)) {
        error_log("RELOADLY_API: Could not obtain a valid token.");
    }
    
    define('RELOADLY_API', $reloadly_key);

// 	$reloadly_key = "";

// 	if (empty($settings['reloaddly_api']) || $settings['reloaddly_api'] == "{}"){
// 		$curl = curl_init();
//         curl_setopt_array($curl, [
//             CURLOPT_URL => 'https://auth.reloadly.com/oauth/token',
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_ENCODING => '',
//             CURLOPT_MAXREDIRS => 10,
//             CURLOPT_TIMEOUT => 0,
//             CURLOPT_FOLLOWLOCATION => true,
//             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//             CURLOPT_SSL_VERIFYPEER => CURL_SSL_VERIFY,
//             CURLOPT_CUSTOMREQUEST => 'POST',
//             CURLOPT_POSTFIELDS => '{
//             	"client_id":"M0GVocnJSKfJjkQ1CdQHMNyKug92ejbS",
//             	"client_secret":"CmxPdndqLZ-UYu2R3Kd0ug7XM1o94k-yHaOwnc2ODH6DZ69vP7tEyBo3M2Fa1sk",
//             	"grant_type":"client_credentials",
//             	"audience":"https://topups.reloadly.com"
//             }',
//             CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
//         ]);

//         $response = curl_exec($curl);
//         $err = curl_error($curl);
//         curl_close($curl);
//         if (!$err) {
//             $res = json_decode($response, true);
//             if (isset($res["access_token"])){
//             	$res["issued_at"] = time();
//             	$reloadly_key = $res["access_token"];
//             	$site_settings->insertUpdateProperty('reloaddly_api', json_encode($res));
//             }
//         }
// 	}else {
// 		$array = json_decode($settings['reloaddly_api'], true);
// 		$expiryTime = $array["issued_at"] + $array["expires_in"];

// 		if (time() >= $expiryTime){
// 			$curl = curl_init();
//             curl_setopt_array($curl, [
//             CURLOPT_URL => 'https://auth.reloadly.com/oauth/token',
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_ENCODING => '',
//             CURLOPT_MAXREDIRS => 10,
//             CURLOPT_TIMEOUT => 0,
//             CURLOPT_FOLLOWLOCATION => true,
//             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//             CURLOPT_SSL_VERIFYPEER => CURL_SSL_VERIFY,
//             CURLOPT_CUSTOMREQUEST => 'POST',
//             CURLOPT_POSTFIELDS => '{
//             	"client_id":"M0GVocnJSKfJjkQ1CdQHMNyKug92ejbS",
//             	"client_secret":"CmxPdndqLZ-UYu2R3Kd0ug7XM1o94k-yHaOwnc2ODH6DZ69vP7tEyBo3M2Fa1sk",
//             	"grant_type":"client_credentials",
//             	"audience":"https://topups.reloadly.com"
//             }',
//             CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
//             ]);

//             $response = curl_exec($curl);
//             $err = curl_error($curl);
//             curl_close($curl);
//             if (!$err) {
//             $res = json_decode($response, true);
//             if (isset($res["access_token"])){
//             	$res["issued_at"] = time();
//             	$reloadly_key = $res["access_token"];
//             	$site_settings->insertUpdateProperty('reloaddly_api', json_encode($res));
//             }
//           }
// 		}
// 		else{
// 			$reloadly_key = $array["access_token"];
// 		}
// 	}
// 	define('RELOADLY_API', $reloadly_key);
	