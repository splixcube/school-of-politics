<?php

namespace Core;

use Core\QB;
use \Dotenv\Dotenv;
use Firebase\JWT\JWT;
use \PHPMailer\PHPMailer\PHPMailer;
use Respect\Validation\Validator as vs;
use Respect\Validation\Exceptions\NestedValidationException;

/**
 * Base Controller (where we integrate all other controllers in our mvC framework)
 */
// abstract class, as we are not going to make methods directly to this class
abstract class Middleware
{
    protected $encrypt_decrypt_key = 'bRuD5WYw5wd0rdHR9yLlM6wt2vteuiniQBqE70nAuhU=';
    protected $token_string;

    // for respect validation
    public $validate_error;
    public $validate_olddata;


    public $validate_success = 0;
    public function unset()
    {
        unset($this->encrypt_decrypt_key);
        unset($this->token_string);
    }
    // replace empty post variables with null
    public function nullify($arr)
    {
        foreach ($arr as $key => $value) {
            // if (is_array($arr[$key])) {
            //     if(count($arr[$key]) == 0) {
            //         $arr[$key] = NULL;
            //     }
            // }else{
            //     if(empty($arr[$key]) && strlen($value) == 0){
            //         $arr[$key] = NULL;
            //     }
            // }
            if (!is_array($value)) {
                if ($value != 0) {
                    $arr[$key] = $arr[$key] ?? NULL ?: NULL;
                }
            } else {
                $arr[$key] = $arr[$key] ?? NULL ?: NULL;
            }
        }
        return $arr;
    }
    // replace empty post variables with null

    // for respect validation


    public function __construct()
    {
        // Enabling ENV Usage
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $this->token_string = substr(str_shuffle($permitted_chars), 0, 32);

        if (!isset($_SESSION['token']) or empty($_SESSION['token']) or !isset($_SESSION['csrf']) or empty($_SESSION['csrf'])) {
            $_SESSION['token'] = $this->token_string;
            $_SESSION['csrf'] = $this->encrypt($this->token_string);
        }
    }

    public function changeDateTime($datetime, $format, $input_tz, $output_tz)
    {
        // Return original string if in and out are the same
        if ($input_tz == $output_tz) {
            return $datetime;
        }
        // Save current timezone setting and set to input timezone
        $original_tz = date_default_timezone_get();
        date_default_timezone_set($input_tz);
        // Get Unix timestamp based on input time zone
        $time = strtotime($datetime);
        // Start working in output timezone
        date_default_timezone_set($output_tz);
        // Calculate result
        $result = date($format, $time);
        // Set timezone correct again
        date_default_timezone_set($original_tz);
        // Return result
        return $result;
    }

    protected function datatoexcel($name, $data)
    {
        $filename = $name . date('Y-m-d') . ".xls";

        header("Content-Type: application/xls");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo "\xEF\xBB\xBF";

        $flag = false;
        foreach ($data as $row) {
            if (!$flag) {
                // display field/column names as first row
                echo implode(",", array_keys($row)) . "\n";
                $flag = true;
            }
            array_walk($row, array($this, 'filterData')); // old technique: 'self::filterData'
            echo implode(",", array_values($row)) . "\n";
        }
        exit;
    }

    public function filterData(&$str)
    {
        $str = str_replace(",", " ", $str);
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", " ", $str);
        if (strstr($str, '"'))
            $str = '"' . str_replace('"', '""', $str) . '"';
    }

    public function encrypt($data)
    {
        // Remove the base64 encoding from our key
        $encryption_key = base64_decode($this->encrypt_decrypt_key);
        // Generate an initialization vector
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        // Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
        // The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
        return base64_encode($encrypted . '::' . $iv);
    }

    public function decrypt($data)
    {
        // Remove the base64 encoding from our key
        $encryption_key = base64_decode($this->encrypt_decrypt_key);
        // To decrypt, split the encrypted data from our IV - our unique separator used was "::"
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
    }

    protected function token($token)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $decrypt_token = $this->decrypt($token);
        if ($_SESSION['token'] == $decrypt_token) {
            $_SESSION['token'] = $this->token_string;
            $_SESSION['csrf'] = $this->encrypt($this->token_string);
            return true;
        } else {
            $_SESSION['token'] = $this->token_string;
            $_SESSION['csrf'] = $this->encrypt($this->token_string);
            return false;
        }
    }



    // Generate Jwt Token
    public function getJwtToken($apiKey, $time = '+180 minutes')
    {

        $fetchApiKeyDetails = QB::table('organizations o')
            ->join('api_keys a', 'a.org_id', '=', 'o.org_id')
            ->select('*')
            ->where([["api_key", "=", $apiKey]])
            ->get('fetchObject');

        $apiData = [];
        if ($fetchApiKeyDetails->success) {
            $apiData = (array) $fetchApiKeyDetails->data;
        }

        // Key file name
        $fileName = md5("{$apiData['oemail']}{$apiKey}");

        // Your passphrase
        $passphrase = md5($apiData['oemail']);

        // Checking whether file exists or not
        // if (!file_exists("{$_SERVER['DOCUMENT_ROOT']}/public/privpem/")) {

        //     // Create a new file or direcotry
        //     mkdir("{$_SERVER['DOCUMENT_ROOT']}/public/privpem/", 0755, true);
        // }

        // Your private key file with passphrase
        // Can be generated with "ssh-keygen -t rsa -m pem"
        $privateKeyFile = "{$_SERVER['DOCUMENT_ROOT']}/public/privpem/{$fileName}.pem";
        // $privateKeyFile = "{$_ENV['rootfolder']}privpem/{$fileName}.pem";


        // Create a private key of type "resource"
        $privateKey = openssl_pkey_get_private(
            file_get_contents($privateKeyFile),
            $passphrase,
        );

        // Set Claims For The Jwt. These Will Be The Data You Want To Include In The Token.
        $payload = array(
            "iss" => "https://api.officeninja.in",
            // Issuer
            "iat" => time(),
            // Issued at (current time)
            "exp" => strtotime($time),
            // Expiration time (1 hour from now)
            // "user_id" => $id
        );

        // Set the secret key that will be used to sign the JWT.
        // $secret_key = $_ENV['jwtSecretKey'];

        // Output the JWT.
        return JWT::encode($payload, $privateKey, 'RS256');
    }

    // Generate Jwt Token




    protected function email($sendType, $setFrom, $setFromName, $addreplyTo, $sendTo, $sendToName, $cc, $ccName, $bcc, $bccName, $subject, $message)
    {
        /* mail send */

        $err = array();
        if (empty($sendType)) {
            $err[] = "Technical Error";
        }
        if (empty($setFrom)) {
            $err[] = "Technical Error";
        }
        if (empty($setFromName)) {
            $err[] = "Technical Error";
        }
        if (empty($addreplyTo) and !is_null($addreplyTo)) {
            $err[] = "Technical Error";
        }
        if (empty($sendTo) or empty($sendToName) or count($sendTo) != count($sendToName)) {
            $err[] = "Technical Error";
        }
        if (!is_null($cc) and !is_null($ccName)) {
            if (count($cc) != count($ccName)) {
                $err[] = "Technical Error";
            }
        }
        if (!is_null($bcc) and !is_null($bccName)) {
            if (count($bcc) != count($bccName)) {
                $err[] = "Technical Error";
            }
        }

        if (empty($subject) and !is_null($subject)) {
            $err[] = "technical error.";
        }
        if (empty($message)) {
            $err[] = "technical error.";
        }

        if (empty($err)) {
            $mail = new PHPMailer;
            $err = array();
            $mail->setFrom($setFrom, $setFromName);
            $mail->addReplyTo($addreplyTo);
            if ($sendType == "single") {
                foreach ($sendTo as $key => $email) {

                    $mail->isSMTP(); //Send using SMTP
                    $mail->Host = 'mail.nyun.in'; //Set the SMTP server to send through
                    $mail->SMTPAuth = true; //Enable SMTP authentication
                    $mail->Username = 'no-reply@nyun.in'; //SMTP username
                    $mail->Password = 'TJsolutions@23'; //SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //Enable implicit TLS encryption
                    $mail->Port = 587; //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                    $mail->addAddress($email, $sendToName[$key]);

                    if (!empty($cc) and !empty($ccName) and !is_null($cc) and !is_null($ccName)) {
                        foreach ($cc as $key => $email) {
                            $mail->addCC($email, $ccName[$key]);
                        }
                    }
                    if (!empty($bcc) and !empty($bccName) and !is_null($bcc) and !is_null($bccName)) {
                        foreach ($bcc as $key => $email) {
                            $mail->addBCC($email, $bccName[$key]);
                        }
                    }
                    if (empty($err)) {
                        $mail->Subject = $subject;
                        $mail->Body = $message;
                        $mail->IsHTML(true);
                        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
                        try {
                            $mail->send();
                            return true;
                        } catch (\Exception $e) {
                            $error = $mail->ErrorInfo;
                            return false;
                        }
                        // if (!$mail->send()) {
                        //     return false;
                        // }else{
                        //     return true;
                        // }
                    } else {
                        return false;
                    }
                }
                // if(empty($err)){
                //     return true;
                // }else{
                //     return false;
                // }
            } else if ($sendType == "group") {
                if (!empty($sendTo) and !empty($sendToName)) {
                    foreach ($sendTo as $key => $email) {
                        $mail->addAddress($email, $sendToName[$key]);
                    }
                }
                if (!empty($cc) and !empty($ccName)) {
                    foreach ($cc as $key => $email) {
                        $mail->addCC($email, $ccName[$key]);
                    }
                }
                if (!empty($bcc) and !empty($bccName)) {
                    foreach ($bcc as $key => $email) {
                        $mail->addBCC($email, $bccName[$key]);
                    }
                }
                $mail->Subject = $subject;
                $mail->Body = $message[$key];
                $mail->IsHTML(true);
                $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
                if (!$mail->send()) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // create a slug
    protected function hyphenize($string)
    {
        $dict = array(
            "I'm" => "I am",
            "thier" => "their",
            // Add your own replacements here
        );
        $finalstring = strtolower(
            preg_replace(
                array('#[\\s-]+#', '#[^A-Za-z0-9. -]+#'),
                array('-', ''),
                $this->cleanString(
                    str_replace(
                        array_keys($dict),
                        array_values($dict),
                        urldecode($string)
                    )
                )
            )
        );
        return preg_replace('/-+/', '-', trim($finalstring, "-")); // Replaces multiple hyphens with single one.
    }

    public function cleanString($text)
    {
        $utf8 = array(
            '/[áàâãªäā]/u' => 'a',
            '/[ÁÀÂÃÄĀ]/u' => 'A',
            '/[ÍÌÎÏĪ]/u' => 'I',
            '/[íìîïī]/u' => 'i',
            '/[éèêëē]/u' => 'e',
            '/[ÉÈÊËĒ]/u' => 'E',
            '/[óòôõºöō]/u' => 'o',
            '/[ÓÒÔÕÖŌ]/u' => 'O',
            '/[úùûüū]/u' => 'u',
            '/[ÚÙÛÜŪ]/u' => 'U',
            '/ç/' => 'c',
            '/Ç/' => 'C',
            '/ñ/' => 'n',
            '/Ñ/' => 'N',
            '/l̥/' => 'l',
            '/L̥/' => 'L',
            '/–/' => '-',
            // UTF-8 hyphen to "normal" hyphen
            '/[’‘‹›‚]/u' => ' ',
            // Literally a single quote
            '/[“”«»„]/u' => ' ',
            // Double quote
            '/ /' => ' ', // nonbreaking space (equiv. to 0x160)
        );
        return preg_replace(array_keys($utf8), array_values($utf8), $text);
    }
    // create a slug

    // Validations
    public function validateData($dataSet)
    {
        // echo "from Middleware";
        $err = $singlearr = array();
        $success = 0;

        $response = $oldData = $postData = $filesData = $message = $data = $labeldata = array();

        // Seprating the dataset into respective data arrays
        foreach ($dataSet as $dkey => $dvalue) {
            if (isset($dvalue[0]) and !empty($dvalue[0]))
                $data[$dkey] = $dvalue[0];
            if (isset($dvalue[1]) and !empty($dvalue[1]) and !is_null($dvalue[1]))
                $labeldata[$dkey] = $dvalue[1];
            if (isset($dvalue[2]) and !empty($dvalue[2]) and !is_null($dvalue[2]))
                $messages[$dkey] = $dvalue[2];
        }
        // -------Seprating the dataset into respective data arrays



        foreach ($data as $key => $value) {
            $optional = 0;
            // Optional Validation
            if (in_array("optional", $value)) {
                $optional = 1;
            }

            // for array inputs
            if (!in_array("image", $value) and is_null($this->array_search_partial($value, 'mime')) and is_null($this->array_search_partial($value, 'resolution')) and is_null($this->array_search_partial($value, 'size'))) {

                if (isset($_POST[$key])) {
                    if (is_array($_POST[$key])) {
                        foreach ($_POST[$key] as $postkey => $postvalue) {
                            $postData[$key][$postkey] = trim($postvalue);
                            if (empty($postData[$key][$postkey]) && strlen($postData[$key][$postkey]) == 0) {
                                unset($postData[$key][$postkey]);
                                unset($_POST[$key][$postkey]);
                            }
                        }
                    } else {
                        $postData[$key][] = trim($_POST[$key]);
                    }
                } else {
                    $postData[$key] = NULL;
                    $_POST[$key] = NULL;
                }
            } else {

                if (is_array($_FILES[$key]['name'])) {
                    foreach ($_FILES[$key]['name'] as $fkey => $fvalue) {
                        if (isset($_FILES[$key]) && $_FILES[$key]['size'][$fkey] != 0 && $_FILES[$key]['error'][$fkey] == 0) {
                            if (is_array($_FILES[$key]['tmp_name'])) {
                                for ($i = 0; $i < count($_FILES[$key]['tmp_name']); $i++) {
                                    $filesData[$i]['name'] = $_FILES[$key]['name'][$i];
                                    $filesData[$i]['tmp_name'] = $_FILES[$key]['tmp_name'][$i];
                                    $filesData[$i]['type'] = $_FILES[$key]['type'][$i];
                                    $filesData[$i]['size'] = $_FILES[$key]['size'][$i];
                                }
                            } else {
                                $filesData[] = $_FILES[$key];
                            }
                        }
                    }
                } else {
                    if (isset($_FILES[$key]) && $_FILES[$key]['size'] != 0 && $_FILES[$key]['error'] == 0) {
                        if (is_array($_FILES[$key]['tmp_name'])) {
                            for ($i = 0; $i < count($_FILES[$key]['tmp_name']); $i++) {
                                $filesData[$i]['name'] = $_FILES[$key]['name'][$i];
                                $filesData[$i]['tmp_name'] = $_FILES[$key]['tmp_name'][$i];
                                $filesData[$i]['type'] = $_FILES[$key]['type'][$i];
                                $filesData[$i]['size'] = $_FILES[$key]['size'][$i];
                            }
                        } else {
                            $filesData[] = $_FILES[$key];
                        }
                    }
                }
            }
            // for array inputs


            // Required Validation
            if (in_array("required", $value)) {
                try {
                    if ($_POST[$key] != 0) {
                        vs::notEmpty()->assert($_POST[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "notEmpty" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "notEmpty" => $label . " is required"
                        ]);
                    }
                }
            }

            // Email Validation
            if (in_array("email", $value)) {
                try {

                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::email()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::email())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "email" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "email" => $label . " must be in valid format"
                        ]);
                    }
                }
            }
            // Phone Number Validation
            if (in_array("phone", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::phone()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::phone())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "phone" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "phone" => $label . " must be in valid format"
                        ]);
                    }
                }
            }
            // Lowercase Validation
            if (in_array("lower", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::stringType()->lowercase()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::stringType()->lowercase())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "lowercase" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "lowercase" => $label . " must be in lowercase"
                        ]);
                    }
                }
            }
            // Uppercase Validation
            if (in_array("upper", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::stringType()->uppercase()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::stringType()->uppercase())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "uppercase" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "uppercase" => $label . " must be in uppercase"
                        ]);
                    }
                }
            }
            // Alphabetic Validation
            if (in_array("alpha", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::alpha()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::alpha())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "alpha" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "alpha" => $label . " must be alphabets only"
                        ]);
                    }
                }
            }
            // AlphaNumeric Validation
            if (in_array("alnum", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::stringType()->alnum()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::stringType()->alnum())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "alnum" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "alnum" => $label . " can only consist of alphabets and numbers"
                        ]);
                    }
                }
            }
            // Domain Validation
            if (in_array("domain", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::domain(false)))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::domain(false))->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "domain" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "domain" => $label . " must be in valid format"
                        ]);
                    }
                }
            }
            // Number Validation
            if (in_array("number", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::number()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::number())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "number" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "number" => $label . " must be in valid format"
                        ]);
                    }
                }
            }
            // Slug Validation
            if (in_array("slug", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::slug()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::slug())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "slug" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "slug" => $label . " must be in valid format"
                        ]);
                    }
                }
            }
            // No White Space Validation
            if (in_array("nospace", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::noWhitespace()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::noWhitespace())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "noWhitespace" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "noWhitespace" => $label . " must not contain any spaces"
                        ]);
                    }
                }
            }
            // Url Validation
            if (in_array("url", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::url()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::url())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "url" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "url" => $label . " must be in valid format"
                        ]);
                    }
                }
            }
            // Countable Validation
            if (in_array("countable", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::countable()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::countable())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "countable" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "countable" => $label . " is not countable"
                        ]);
                    }
                }
            }
            // Json Validation
            if (in_array("json", $value)) {
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::json()))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::json())->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "json" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "json" => $label . " is not in valid JSON format"
                        ]);
                    }
                }
            }
            // Image Validation
            if (in_array("image", $value)) {

                try {
                    if ($optional == 1) {
                        for ($i = 0; $i < count($filesData); $i++) {
                            vs::optional(vs::image())->assert($filesData[$i]['tmp_name']);
                        }
                    } else {
                        if (!empty($filesData)) {
                            for ($i = 0; $i < count($filesData); $i++) {
                                vs::image()->assert($filesData[$i]['tmp_name']);
                            }
                        } else {
                            $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                            $err[] = array($label . " is not a valid format");
                        }
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "image" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "image" => $label . " is not a valid format"
                        ]);
                    }
                }
            }
            // Image Resolution Validation
            $res_index = $this->array_search_partial($value, 'resolution');
            if (isset($res_index)) {
                $resdata = explode("|", $value[$res_index]);
                if (isset($resdata[1])) {
                    $checkData = $resdata[1];
                    try {
                        if ($optional == 1) {
                            for ($i = 0; $i < count($filesData); $i++) {
                                vs::optional(vs::resolution($checkData))->assert($filesData[$i]['tmp_name']);
                            }
                        } else {
                            for ($i = 0; $i < count($filesData); $i++) {
                                vs::resolution($checkData)->assert($filesData[$i]['tmp_name']);
                            }
                        }
                    } catch (NestedValidationException $exception) {
                        if (isset($messages[$key])) {
                            $err[] = $exception->getMessages([
                                "resolution" => $messages[$key]
                            ]);
                        } else {
                            $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                            $err[] = $exception->getMessages([
                                "resolution" => $label . " must not be less than " . $resdata[1]
                            ]);
                        }
                    }
                } else {
                    $err[] = [
                        'resolution' => 'check variable passed with min validation'
                    ];
                }
            }
            // File Size Validation
            $size_index = $this->array_search_partial($value, 'size');
            if (isset($size_index)) {
                $size = explode("|", $value[$size_index]);
                $sizeVals = explode(",", $size[1]);
                if ($sizeVals[0] == 'null') {
                    $sizeVals[0] = NULL;
                }
                try {
                    if ($optional == 1) {
                        for ($i = 0; $i < count($filesData); $i++) {
                            vs::optional(vs::size(...$sizeVals))->assert($filesData[$i]['tmp_name']);
                        }
                    } else {
                        for ($i = 0; $i < count($filesData); $i++) {
                            vs::size(...$sizeVals)->assert($filesData[$i]['tmp_name']);
                        }
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "size" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        if (is_null($sizeVals[0])) {
                            $err[] = $exception->getMessages([
                                "size" => $label . " must be less than " . $sizeVals[1]
                            ]);
                        } else {
                            $err[] = $exception->getMessages([
                                "size" => $label . " must be in between " . $sizeVals[0] . " and " . $sizeVals[1]
                            ]);
                        }
                    }
                }
            }
            // Length Validation
            $length_index = $this->array_search_partial($value, 'length');
            if (isset($length_index)) {
                $length = explode("|", $value[$length_index]);
                $intlength = explode(",", $length[1]);
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::length(...$intlength)))->assert($postData[$key]);
                    } else {
                        // vs::each(vs::length(...$intlength))->assert($postData[$key]);
                        vs::arrayVal()->each(vs::length(...$intlength))->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err = $exception->getMessages([
                            "length" => $messages[$key],
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $lengthcount = explode(",", $length[1]);
                        if ($lengthcount[0] == $lengthcount[1]) {
                            $lengthcount_message = " must be " . $lengthcount[0];
                        } else {
                            $lengthcount_message = " must be between " . $lengthcount[0] . " to " . $lengthcount[1];
                        }
                        $err[] = $exception->getMessages([
                            "length" => $label . $lengthcount_message . " characters"
                        ]);
                    }

                }

                exit();
            }
            // DateTime Validation
            $datetime_index = $this->array_search_partial($value, 'datetime');
            if (isset($datetime_index)) {
                $datedata = explode("|", $value[$datetime_index]);
                if (isset($datedata[1])) {
                    $format = $datedata[1];
                } else {
                    $format = NULL;
                }
                try {
                    if ($optional == 1) {
                        vs::arrayVal()->each(vs::optional(vs::dateTime($format)))->assert($postData[$key]);
                    } else {
                        vs::arrayVal()->each(vs::dateTime($format))->assert($postData[$key]);
                    }
                } catch (NestedValidationException $exception) {
                    if (isset($messages[$key])) {
                        $err[] = $exception->getMessages([
                            "datetime" => $messages[$key]
                        ]);
                    } else {
                        $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                        $err[] = $exception->getMessages([
                            "datetime" => $label . " must be in valid format"
                        ]);
                    }
                }
            }
            // Equals Validation
            $equals_index = $this->array_search_partial($value, 'equals');
            if (isset($equals_index)) {
                $equalsdata = explode("|", $value[$equals_index]);
                if (isset($equalsdata[1])) {
                    $checkData = $postData[$equalsdata[1]];
                    try {
                        vs::equals($checkData)->assert($postData[$key]);
                    } catch (NestedValidationException $exception) {
                        if (isset($messages[$key])) {
                            $err[] = $exception->getMessages([
                                "equals" => $messages[$key]
                            ]);
                        } else {
                            $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                            $err[] = $exception->getMessages([
                                "equals" => $equalsdata[1] . " must be equal"
                            ]);
                        }
                    }
                } else {
                    $err[] = [
                        'max' => 'check variable passed with max validation'
                    ];
                }
            }
            // Max Validation (to compare Numbers)
            $max_index = $this->array_search_partial($value, 'max');
            if (isset($max_index)) {
                $maxdata = explode("|", $value[$max_index]);
                if (isset($maxdata[1])) {
                    $checkData = intval($maxdata[1]);
                    try {
                        // vs::max((int)$checkData)->assert($postData[$key]);
                        if ($optional == 1) {
                            vs::arrayVal()->each(vs::optional(vs::max($checkData)))->assert($postData[$key]);
                        } else {
                            vs::arrayVal()->each(vs::max($checkData))->assert($postData[$key]);
                        }
                    } catch (NestedValidationException $exception) {
                        if (isset($messages[$key])) {
                            $err[] = $exception->getMessages([
                                "max" => $messages[$key]
                            ]);
                        } else {
                            $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                            $err[] = $exception->getMessages([
                                "max" => $label . " must not be greater than" . $maxdata[1]
                            ]);
                        }
                    }
                } else {
                    $err[] = [
                        'max' => 'check variable passed with max validation'
                    ];
                }
            }
            // Min Validation (to compare Numbers)
            $min_index = $this->array_search_partial($value, 'min');
            if (isset($min_index)) {
                $mindata = explode("|", $value[$min_index]);
                if (isset($mindata[1])) {
                    $checkData = intval($mindata[1]);
                    try {
                        if ($optional == 1) {
                            vs::arrayVal()->each(vs::optional(vs::min($checkData)))->assert($postData[$key]);
                        } else {
                            vs::arrayVal()->each(vs::min($checkData))->assert($postData[$key]);
                        }
                    } catch (NestedValidationException $exception) {
                        if (isset($messages[$key])) {
                            $err[] = $exception->getMessages([
                                "min" => $messages[$key]
                            ]);
                        } else {
                            $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                            $err[] = $exception->getMessages([
                                "min" => $label . " must not be less than" . $maxdata[1]
                            ]);
                        }
                    }
                } else {
                    $err[] = [
                        'min' => 'check variable passed with min validation'
                    ];
                }
            }
            // Mime Type Validations
            $mime_index = $this->array_search_partial($value, 'mime');
            if (isset($mime_index)) {
                $mimedata = explode("|", $value[$mime_index]);
                if (isset($mimedata[1])) {
                    $checkData = explode(",", $mimedata[1]);
                    $inputFile = $filesData;
                    if (empty($filesData)) {
                        $checkIndex = false;
                    } else {
                        $checkIndex = true;
                    }

                    if ($checkIndex !== false) {
                        try {
                            if ($optional == 1) {
                                for ($i = 0; $i < count($filesData); $i++) {
                                    $checkIndex = array_search(mime_content_type($filesData[$i]['tmp_name']), $checkData);
                                    vs::optional(vs::mimetype($checkData[$checkIndex]))->assert($filesData[$i]['tmp_name']);
                                }
                            } else {
                                for ($i = 0; $i < count($filesData); $i++) {
                                    $checkIndex = array_search(mime_content_type($filesData[$i]['tmp_name']), $checkData);
                                    vs::mimetype($checkData[$checkIndex])->assert($filesData[$i]['tmp_name']);
                                }
                            }
                        } catch (NestedValidationException $exception) {
                            if (isset($messages[$key])) {
                                $err[] = $exception->getMessages([
                                    "mimetype" => $messages[$key]
                                ]);
                            } else {
                                $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                                $err[] = $exception->getMessages([
                                    "mimetype" => $label . " must be of valid extensions"
                                ]);
                            }
                        }
                    } else {
                        if ($optional != 1) {
                            if (!empty($inputFile)) {
                                $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                                $err[] = ["mime" => $label . " must be of valid extensions"];
                            } else {
                                $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                                $err[] = ["mime" => $label . " cannot be empty"];
                            }
                        }
                    }
                } else {
                    $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                    $err[] = ["mime" => $label . " must be of valid extensions"];
                }
            }

            // Unique Validation
            $unique_index = $this->array_search_partial($value, 'unique');
            if (isset($unique_index)) {
                $uniquedata = explode("|", $value[$unique_index]);
                $data = explode(",", $uniquedata[1]);
                $tableName = $data[0];
                $columnName = $data[1];

                if (isset($data[0]) and isset($data[1])) {
                    $checkUnique = QB::table($tableName)
                        ->select('*')
                        ->where([[$columnName, "=", $postData[$key][0]]])
                        ->get('fetchObject');
                    if ($checkUnique->success) {
                        if (isset($messages[$key])) {
                            $err[] = [
                                "unique" => $messages[$key]
                            ];
                        } else {
                            $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                            $err[] = [
                                "unique" => $label . " must be unique.",
                            ];
                        }
                    }
                } else {
                    $err[] = [
                        'unique' => 'check table and column name passed with unique validation'
                    ];
                }
            }

            // Regex Validation to check regex
            $regex_index = $this->array_search_partial($value, 'regex');
            if (isset($regex_index)) {
                $regexData = explode("|", $value[$regex_index], 2);
                if (isset($regexData[1])) {
                    $checkData = $regexData[1];
                    try {
                        if ($optional == 1) {
                            vs::arrayVal()->each(vs::optional(vs::regex($checkData)))->assert($postData[$key]);
                        } else {
                            vs::arrayVal()->each(vs::regex($checkData))->assert($postData[$key]);
                        }
                    } catch (NestedValidationException $exception) {
                        if (isset($messages[$key])) {
                            $err[] = $exception->getMessages([
                                "regex" => $messages[$key]
                            ]);
                        } else {
                            $label = isset($labeldata[$key]) ? $labeldata[$key] : $key;
                            $err[] = $exception->getMessages([
                                "regex" => $label . " invalid value."
                            ]);
                        }
                    }
                } else {
                    $err[] = [
                        'regex' => 'check expression passed with regex validation'
                    ];
                }
            }
        }
        // collecting old data here eliminates empty array inputs
        $_POST = $this->nullify($_POST);
        if (empty($err)) {
            $success = 1;
            $this->validate_error = $err;
            $this->validate_olddata = $_POST;
            $this->validate_success = $success;
        } else {

            // combining child error array to main array and then reindexing
            foreach ($err as $errkey => $errvalue) {
                foreach ($errvalue as $ekey => $evalue) {
                    $err[] = $evalue;
                }
                unset($err[$errkey]);
            }

            $err = array_values(array_unique($err));
            // combining child error array to main array and then reindexing

            $this->validate_error = $err;
            $this->validate_olddata = $_POST;
            $this->validate_success = $success;
        }
        $this->unset();
        return $this;
    }

    function array_search_partial($arr, $keyword)
    {
        foreach ($arr as $index => $string) {
            if (!empty($string) && strpos($string, $keyword) !== FALSE)
                return $index;
        }
    }
    // Validation

    /**
     * fileUpload: File Upload with image conversion
     *
     * @param  mixed $dir
     * @param  mixed $file
     * @param  mixed $convert
     * @param  mixed $quality
     * @param  mixed $delete
     * @return string
     */
    public function fileUpload($dir, $file, $index = NULL, $convert = NULL, $quality = 100, $delete = false)
    {
        $imageArr = array();
        if (!is_null($index)) {
            $fileName = $file["name"];
            $fileTemp = $file["tmp_name"];
            $i = $index;

            $ext = pathinfo($fileName[$i], PATHINFO_EXTENSION);
            $s = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 5)), 0, 5);
            $image = basename($s . "." . $ext);
            // Image Conversion
            if (isset($convert) and !empty($convert) and $convert == "webp") {
                move_uploaded_file($fileTemp[$i], $dir . "/" . $image);
                // Creating New File Name
                $newImage = basename($s . ".webp");
                // Jpeg to webp Conversion
                if ($ext == "jpeg" or $ext == "jpg") {
                    $imgObj = imagecreatefromjpeg($dir . "/" . $image);
                    imagewebp($imgObj, $dir . "/" . $newImage, $quality);
                    imagedestroy($imgObj);
                    // Deleting Jpeg file on $delete is true
                    if (file_exists($dir . "/" . $image) and $delete == true) {
                        unlink($dir . "/" . $image);
                        $imageArr[] = $newImage;
                    } else {
                        $imageArr[] = $image;
                    }
                }
                // Png to webp Conversion
                if ($ext == "png") {
                    $imgObj = imagecreatefrompng($dir . "/" . $image);
                    imagewebp($imgObj, $dir . "/" . $newImage, $quality);
                    imagedestroy($imgObj);
                    // Deleting Jpeg file on $delete is true
                    if (file_exists($dir . "/" . $image) and $delete == true) {
                        unlink($dir . "/" . $image);
                        $imageArr[] = $newImage;
                    } else {
                        $imageArr[] = $image;
                    }
                }
            } else {
                if (move_uploaded_file($fileTemp[$i], $dir . "/" . $image)) {
                    $imageArr[] = $image;
                }
            }
        } else {
            if (!is_array($file["name"])) {
                $fileName[] = $file["name"];
                $fileTemp[] = $file["tmp_name"];
            } else {
                $fileName = $file["name"];
                $fileTemp = $file["tmp_name"];
            }

            for ($i = 0; $i < count($fileName); $i++) {
                $ext = pathinfo($fileName[$i], PATHINFO_EXTENSION);
                $s = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 5)), 0, 5);
                $image = basename($s . "." . $ext);
                // var_dump($ext);
                // Image Conversion
                if (isset($convert) and !empty($convert) and $convert == "webp") {
                    move_uploaded_file($fileTemp[$i], $dir . "/" . $image);
                    // Creating New File Name
                    $newImage = basename($s . ".webp");
                    // Jpeg to webp Conversion
                    if ($ext == "jpeg" or $ext == "jpg") {
                        $imgObj = imagecreatefromjpeg($dir . "/" . $image);
                        imagewebp($imgObj, $dir . "/" . $newImage, $quality);
                        imagedestroy($imgObj);
                        // Deleting Jpeg file on $delete is true
                        if (file_exists($dir . "/" . $image) and $delete == true) {
                            unlink($dir . "/" . $image);
                            $imageArr[] = $newImage;
                        } else {
                            $imageArr[] = $image;
                        }
                    }
                    // Png to webp Conversion
                    if ($ext == "png") {
                        $imgObj = imagecreatefrompng($dir . "/" . $image);
                        imagewebp($imgObj, $dir . "/" . $newImage, $quality);
                        imagedestroy($imgObj);
                        // Deleting Jpeg file on $delete is true
                        if (file_exists($dir . "/" . $image) and $delete == true) {
                            unlink($dir . "/" . $image);
                            $imageArr[] = $newImage;
                        } else {
                            $imageArr[] = $image;
                        }
                    }
                } else {
                    if (move_uploaded_file($fileTemp[$i], $dir . "/" . $image)) {
                        $imageArr[] = $image;
                    }
                }
            }
        }
        if (!empty($imageArr)) {
            $filenames = implode("~@~", $imageArr);
            return rtrim($filenames, "~@~");
        } else {
            return NULL;
        }
    }





    /**
     * createThumbnail: Image Thumbnail with image conversion
     *
     * @param  mixed $file
     * @param  mixed $fileName
     * @param  mixed $dir
     * @param  mixed $thumb_width
     * @param  mixed $thumb_height
     * @param  mixed $quality
     * @param  mixed $output
     * @return string
     */
    public function createThumbnail($file, $fileName, $dir, $thumb_width, $thumb_height, $quality, $output = "jpg")
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        if ($ext == "webp") {
            $imgObj = imagecreatefromwebp($file);
        }
        if ($ext == "jpeg" or $ext == "jpg") {
            $imgObj = imagecreatefromjpeg($file);
        }
        $outputDir = $dir . "thumbnail/";
        if (!is_dir($dir . "thumbnail/")) {
            mkdir($dir . "thumbnail/");
        }

        $width = imagesx($imgObj);
        $height = imagesy($imgObj);

        $original_aspect = $width / $height;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $width / ($height / $thumb_height);
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $height / ($width / $thumb_width);
        }

        $thumb = imagecreatetruecolor($thumb_width, $thumb_height);

        // Resize and crop
        imagecopyresampled(
            $thumb,
            $imgObj,
            0 - ($new_width - $thumb_width) / 2, // Center the image horizontally
            0 - ($new_height - $thumb_height) / 2,
            // Center the image vertically
            0,
            0,
            $new_width,
            $new_height,
            $width,
            $height
        );
        if ($output === "webp") {
            //The path that we want to save our webp file to.
            $webpName = strtok($fileName, '.') . ".webp";

            //Create the webp image.
            imagewebp($thumb, $outputDir . $webpName, $quality);
            $finalImage = $webpName;
        }

        if ($output === "jpg") {
            $jpegName = strtok($fileName, '.') . ".jpg";
            imagejpeg($thumb, $outputDir . $jpegName, $quality);
            $finalImage = $jpegName;
        }

        // var_dump($fileName);
        imagedestroy($imgObj);
        imagedestroy($thumb);
        return $finalImage;
    }
}