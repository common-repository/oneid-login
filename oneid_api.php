<?php

/*
 * Copyright 2012  by OneID
 */

class OneIDAPI {

    function OneID_LoginChallenge($callback_url, $attr = "") {
        $attr = "";

        if (!$attr) {
            $attr = "email[email] name[first_name] name[last_name]";
        }

        return array(
            "attr" => $attr, // OneID attributes to create an account here
            "callback" => $callback_url
        );
    }

    /**
     * Generate OneID Login Button.
     *
     * @param  string $attr Requested user info returned from OneID
     * @param  string $callback Url for OneID callback
     * @return string
     */
    function OneID_Button($attr, $callback_url) {

        /*
         * You could also put this javascript in your front end templates yourself.
         * 
         */
        $params = json_encode(
            array("challenge" => self::OneID_LoginChallenge($callback_url, $attr))
        );

        $js = "<span class='oneid_login_ctr'></span>";
        $js.= "<script type='text/javascript'>";
        $js.= "OneID.config({ buttons:{";
        $js.= "'signin .oneid_login_ctr' : [" . $params . "]";
        $js.= "}});";
        $js.= "</script>";

        return $js;
    }

    /**
     * Generates nonce.
     *
     * @return string
     */
    function OneID_MakeNonce() {
        $arr = self::_call_OneID("make_nonce");
        return $arr['nonce'];
    }

    /**
     * Validate the user against OneID
     *
     * @return array
     */
    function OneID_Response() {
        $resp = json_decode(file_get_contents('php://input'), true);
	
        //Build object of what needs to go to validation server.
        $validate_data = array(
            "nonces" => $resp["nonces"],
            "attr_claim_tokens" => $resp["attr_claim_tokens"],
            "uid" => $resp["uid"]
        );
        $validate = self::_call_OneID("validate", json_encode($validate_data));

        if (!isset($validate["errorcode"]) || $validate["errorcode"] != 0) {
            return FALSE;
        }
        $arr = array_merge($resp, $validate);



        return $arr;
    }

    /**
     * Tell OneID service where to send the logged in user.
     *
     * @param string $page Redirect user to url
     */
    function OneID_Redirect($page) {
        return ('{"error":"success","url":"' . $page . '"}');
    }

    /**
     * Make call to OneID service.
     *
     * @param string $method OneID service method
     * @param array $post (defaul: null)
     * @return array
     */
    function _call_OneID($method, $post = null) {
        $oneid_server = 'https://keychain.oneid.com';

        if ($options = get_option('oneid_options')) {
            $oneid_api_id = $options["oneid_api_id"];
            $oneid_api_key = $options["oneid_api_key"];
        }

        $scope = "";
        $ch = curl_init($oneid_server . $scope . "/" . $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $oneid_api_id . ":" . $oneid_api_key);
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $json = curl_exec($ch);
        curl_close($ch);
        return json_decode($json, true);
    }
}

?>
