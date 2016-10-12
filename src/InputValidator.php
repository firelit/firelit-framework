<?php

namespace Firelit;

class InputValidator
{

    const
        NAME = 1,
        ORG_NAME = 2,
        ADDRESS = 3,
        CITY = 4,
        STATE = 5,
        ZIP = 6,
        COUNTRY = 7,
        PHONE = 8,
        EMAIL = 9,
        CREDIT_ACCT = 10,
        CREDIT_EXP = 11,
        CREDIT_CVV = 12,
        ACH_ROUT = 13,
        ACH_ACCT = 14,
        ACH_TYPE = 15,
        URL = 16;

    const
        TYPE_DEFAULT = 100, // For general use (sensitive data is automatically masked)
        TYPE_GATEWAY = 200, // For payment gateway
        TYPE_DB = 300; // To get a database-ready version

    protected $type, $value, $region, $required;

    public function __construct($type, $value, $region = false)
    {
        $this->type = $type;
        $this->value = filter_var(trim($value), FILTER_UNSAFE_RAW, array(
            'flags' => FILTER_FLAG_STRIP_LOW
        ));
        $this->region = $region;
        $this->required = true;
    }

    public function setRequired($required = true)
    {
        $this->required = $required;
    }

    public function isValid()
    {
        return self::validate($this->type, $this->value, $this->region, $this->required);
    }

    public function getNormalized($returnType = self::TYPE_DEFAULT)
    {

        switch ($this->type) {
            case self::NAME:
            case self::ORG_NAME:
            case self::CITY:

                return Strings::nameFix($this->value);

            case self::ADDRESS:

                return Strings::addressFix($this->value);

            case self::STATE:
                $state = $this->value;

                if (in_array($this->region, array('US', 'CA', 'MX'))) {
                    return substr(mb_strtoupper(trim($state)), 0, 2);
                }

                if (strlen($state) <= 3) {
                    return mb_strtoupper($state);
                }

                return Strings::ucwords($state);

            case self::ZIP:
                $zip = mb_strtoupper($this->value);
                if ($this->region == 'CA') {
                    $zip = substr($zip, 0, 3) .' '. substr($zip, -3);
                }

                return $zip;

            case self::COUNTRY:
                return substr(mb_strtoupper($this->value), 0, 2);

            case self::PHONE:
                $phone = preg_replace('/\s{2,}/', ' ', trim($this->value));

                if (strlen($phone) < 5) {
                    return $phone;
                }

                if (in_array($this->region, array('US', 'CA'))) {
                    $phone = preg_replace('/[^0-9]+/', '', $phone);

                    if ((strlen($phone) > 10) && (substr($phone, 0, 1) == '1')) {
                        $phone = substr($phone, 1);
                    }

                    $phone = "(". substr($phone, 0, 3) .") ". substr($phone, 3, 3) ."-". substr($phone, 6, 4) . trim(' '. substr($phone, 10));
                } else {
                    $phone = preg_replace('/[^0-9\-\+\(\) ]+/', '', $phone);
                }

                return $phone;

            case self::EMAIL:
                return mb_strtolower($this->value);

            case self::CREDIT_ACCT:
                $num = preg_replace('/\D+/', '', $this->value);

                if ($returnType == self::TYPE_GATEWAY) {
                    return $num;
                }

                return self::lastfour($num, 'x');

            case self::CREDIT_EXP:
                $exp = $this->value;

                if (preg_match('/^\d{2}[\/-]?\d{2}$/', $exp)) {
                    $mo = substr($exp, 0, 2);
                    $yr = substr($exp, -2);
                    $exp = strtotime($mo.'/01/20'.$yr);
                } elseif (preg_match('/^\d{1}[\/-]?\d{2}$/', $exp)) {
                    $mo = '0'.substr($exp, 0, 1);
                    $yr = substr($exp, -2);
                    $exp = strtotime($mo.'/01/20'.$yr);
                } else {
                    // Shot in the dark
                    $exp = strtotime($exp);
                }

                if ($returnType == self::TYPE_GATEWAY) {
                    return date('my', $exp);
                }

                if ($returnType == self::TYPE_DB) {
                    return '20'. $yr .'-'. $mo .'-'. date('t', $exp);
                }

                return date('m/y', $exp);

            case self::CREDIT_CVV:
                $num = preg_replace('/\D+/', '', $this->value);

                if ($returnType == self::TYPE_GATEWAY) {
                    return $num;
                }

                return str_pad('', strlen($num), 'x');

            case self::ACH_ROUT:
                $num = preg_replace('/\D+/', '', $this->value);

                return $num;

            case self::ACH_ACCT:
                $num = preg_replace('/\D+/', '', $this->value);

                if ($returnType == self::TYPE_GATEWAY) {
                    return $num;
                }

                return self::lastfour($num, 'x');

            case self::ACH_TYPE:
                $type = mb_strtoupper(substr($this->value, 0, 1));

                if (in_array($returnType, array(self::TYPE_DB, self::TYPE_GATEWAY))) {
                    return $type;
                }

                if ($type == 'C') {
                    return 'Checking';
                } elseif ($type == 'S') {
                    return 'Savings';
                } else {
                    return '';
                }

            case self::URL:
                $url = parse_url($this->value);
                if (empty($url['scheme'])) {
                    $url = parse_url('http://'. $this->value); // Add a scheme for proper parsing
                }

                if (empty($url['path'])) {
                    $url['path'] = '/';
                }

                return $url['scheme'] .'://'. strtolower($url['host']) . (!empty($url['port']) ? ':'. $url['port'] : '') . $url['path'] . (!empty($url['query']) ? '?'. $url['query'] : '');

            default:
                return false;
        }
    }

    public static function validate($type, $value, $region = false, $required = true)
    {
        if (empty($value)) {
            return !$required;
        }

        switch ($type) {
            case self::NAME:
            case self::CITY:
                if (preg_match('/^\d/', $value)) {
                    return false;
                }

                return preg_match('/^\p{L}[\p{L} \-\'\+&\.]*[\p{L}\.]$/u', $value);

            case self::ORG_NAME:
                return preg_match('/^[\p{L}\d].*[\p{L}\d\.\)\]\!\?]$/u', $value);

            case self::ADDRESS:
                // Must contain at least one digit and 2 letters
                return preg_match('/\d+/', $value) && preg_match('/[A-Za-z]{2,}/', $value);

            case self::STATE:
                if (in_array($region, array('US', 'CA', 'MX'))) {
                    return ( States::check($region, mb_strtoupper($value)) !== false );
                } else {
                    return preg_match('/^(.+)$/', $value);
                }

            case self::ZIP:
                if ($region == 'US') {
                    return preg_match('/^\d{5}(\-\d{4})?$/', $value);
                } elseif ($region == 'CA') {
                    return preg_match('/^\D\d\D(\s)?\d\D\d$/', $value);
                } elseif ($region == 'MX') {
                    return preg_match('/^\d{5}$/', $value);
                } else {
                    return preg_match('/^(.+)$/', $value);
                }

            case self::COUNTRY:
                return Countries::check($value);

            case self::PHONE:
                $temp = preg_replace('/\D+/', '', $value);

                if (in_array($region, array('US', 'CA'))) {
                    return preg_match('/^(1)?[2-9]\d{9}$/', $temp);
                } else {
                    return preg_match('/^\d{4,}$/', $temp);
                }

            case self::EMAIL:
                return (filter_var($value, FILTER_VALIDATE_EMAIL) !== false);

            case self::CREDIT_ACCT:
                $value = preg_replace('/\s+/', '', $value);

                if (!preg_match('/^\d{15,16}$/', $value)) {
                    return false;
                }
                if (!preg_match('/^[3-6]/', $value)) {
                    return false;
                }

                return self::checkLuhn($value);

            case self::CREDIT_EXP:
                $value = preg_replace_callback('/^(\d{1,2})[\/\-]?(\d{2})$/', function ($matches) {
                    if (strlen($matches[1]) == 1) {
                        $matches[1] = '0'.$matches[1];
                    }
                    return $matches[1] . $matches[2];
                }, $value);

                $mo = intval(substr($value, 0, 2));
                $yr = intval(substr($value, -2));

                if (($mo < 1) || ($mo > 12)) {
                    return false;
                }
                if (($yr < intval(date('y'))) || ($yr > (intval(date('y')) + 15))) {
                    return false;
                }

                return preg_match('/^\d{4}$/', $value);

            case self::CREDIT_CVV:
                return preg_match('/^\d{3,4}$/', $value);

            case self::ACH_ROUT:
                if (!preg_match('/^\d{9}$/', $value)) {
                    return false;
                }

                return self::checkChecksum($value);

            case self::ACH_ACCT:
                $value = preg_replace('/[\s\-]+/', '', $value);

                return preg_match('/^\d{4,25}$/', $value);

            case self::ACH_TYPE:
                return preg_match('/^(C|S)$/i', substr($value, 0, 1));

            case self::URL:
                $url = parse_url($value);

                if (!$url) {
                    return false;
                }

                if (empty($url['scheme'])) {
                    $url = parse_url('http://'. $value); // Add a scheme for proper parsing
                }

                if (strlen($url['scheme']) && !in_array($url['scheme'], array('http', 'https'))) {
                    return false;
                }
                if (!isset($url['host']) && isset($url['path'])) {
                    $url['host'] = $url['path'];
                    $url['path'] = '';
                }
                if (!preg_match('/^([a-z0-9\-]+\.)+([a-z]{2,})$/i', $url['host'])) {
                    return false;
                }

                return true;

            default:
                return false;
        }
    }

    public static function lastfour($number, $padChar = 'x')
    {

        $len = strlen($number);
        if ($len == 0) {
            return '';
        }

        $lastLen = intval(floor($len / 2));
        if ($lastLen > 4) {
            $lastLen = 4;
        }
        $lastFour = substr($number, -$lastLen, $lastLen);
        $lastFour = str_pad($lastFour, $len, $padChar, STR_PAD_LEFT);

        return $lastFour;
    }

    public static function checkChecksum($number)
    {

        settype($number, 'string');

        $sum = 3 * ( intval(substr($number, 0, 1)) + intval(substr($number, 3, 1)) + intval(substr($number, 6, 1)) );
        $sum += 7 * ( intval(substr($number, 1, 1)) + intval(substr($number, 4, 1)) + intval(substr($number, 7, 1)) );
        $sum += intval(substr($number, 2, 1)) + intval(substr($number, 5, 1)) + intval(substr($number, 8, 1));

        return (($sum % 10) === 0);
    }

    public static function checkLuhn($number)
    {

        settype($number, 'string');

        $sumTable = array(
            array(0,1,2,3,4,5,6,7,8,9),
            array(0,2,4,6,8,1,3,5,7,9)
        );

        $sum = 0;
        $flip = 0;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $sum += $sumTable[ $flip++ & 0x1 ][ $number[$i] ];
        }

        return (($sum % 10) === 0);
    }

}
