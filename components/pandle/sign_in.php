<?php
  function signIn () {
    $PANDLE_USERNAME = $_ENV['PANDLE_USERNAME'];
    $PANDLE_PASSWORD = $_ENV['PANDLE_PASSWORD'];

    $ch = curl_init("https://my.pandle.com/api/v1/auth/sign_in");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "email=$PANDLE_USERNAME&password=$PANDLE_PASSWORD");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Accept: application/json",
        "Content-Type: application/x-www-form-urlencoded",
        "Origin: https://api.galexia.agency"
    ));
    // this function is called by curl for each header received
    curl_setopt($ch, CURLOPT_HEADERFUNCTION,
      function($curl, $header) use (&$headers)
      {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2) // ignore invalid headers
          return $len;

        $headers[strtolower(trim($header[0]))][] = trim($header[1]);

        return $len;
      }
    );

    curl_exec($ch);
    return $headers;
  }