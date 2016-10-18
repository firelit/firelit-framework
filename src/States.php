<?PHP

namespace Firelit;

class States
{

    static public $list = array(
        "US" => array(
            "AL" => "Alabama",
            "AK" => "Alaska",
            "AS" => "American Samoa",
            "AZ" => "Arizona",
            "AR" => "Arkansas",
            "CA" => "California",
            "CO" => "Colorado",
            "CT" => "Connecticut",
            "DE" => "Delaware",
            "DC" => "Dist of Columbia",
            "FM" => "Fed States Of Micronesia",
            "FL" => "Florida",
            "GA" => "Georgia",
            "GU" => "Guam",
            "HI" => "Hawaii",
            "ID" => "Idaho",
            "IL" => "Illinois",
            "IN" => "Indiana",
            "IA" => "Iowa",
            "KS" => "Kansas",
            "KY" => "Kentucky",
            "LA" => "Louisiana",
            "ME" => "Maine",
            "MH" => "Marshall Islands",
            "MD" => "Maryland",
            "MA" => "Massachusetts",
            "MI" => "Michigan",
            "MN" => "Minnesota",
            "MS" => "Mississippi",
            "MO" => "Missouri",
            "MT" => "Montana",
            "NE" => "Nebraska",
            "NV" => "Nevada",
            "NH" => "New Hampshire",
            "NJ" => "New Jersey",
            "NM" => "New Mexico",
            "NY" => "New York",
            "NC" => "North Carolina",
            "ND" => "North Dakota",
            "MP" => "Northern Mariana Islands",
            "OH" => "Ohio",
            "OK" => "Oklahoma",
            "OR" => "Oregon",
            "PW" => "Palau",
            "PA" => "Pennsylvania",
            "PR" => "Puerto Rico",
            "RI" => "Rhode Island",
            "SC" => "South Carolina",
            "SD" => "South Dakota",
            "TN" => "Tennessee",
            "TX" => "Texas",
            "UT" => "Utah",
            "VT" => "Vermont",
            "VI" => "Virgin Islands",
            "VA" => "Virginia",
            "WA" => "Washington",
            "WV" => "West Virginia",
            "WI" => "Wisconsin",
            "WY" => "Wyoming",
            "AA" => "Armed Forces (AA)",
            "AE" => "Armed Forces (AE)",
            "AP" => "Armed Forces (AP)"
        ),
        "CA" => array(
            "AB" => "Alberta",
            "BC" => "British Columbia",
            "MB" => "Manitoba",
            "NB" => "New Brunswick",
            "NL" => "Newfoundland and Labrador",
            "NT" => "Northwest Territories",
            "NS" => "Nova Scotia",
            "NU" => "Nunavut",
            "ON" => "Ontario",
            "PE" => "Prince Edward Island",
            "QC" => "Quebec",
            "SK" => "Saskatchewan",
            "YT" => "Yukon"
        ),
        "MX" => array(
            "AG" => "Aguascalientes",
            "BC" => "Baja California Norte",
            "BS" => "Baja California Sur",
            "CM" => "Campeche",
            "CS" => "Chiapas",
            "CH" => "Chihuahua",
            "CO" => "Coahuila",
            "CL" => "Colima",
            "DF" => "Distrito Federal",
            "DG" => "Durango",
            "GT" => "Guanajuato",
            "GR" => "Guerrero",
            "HG" => "Hidalgo",
            "JA" => "Jalisco",
            "MX" => "Mexico",
            "MI" => "Michoacan", // Michoacán
            "MO" => "Morelos",
            "NA" => "Nayarit",
            "NL" => "Nuevo Leon",
            "OA" => "Oaxaca",
            "PU" => "Puebla",
            "QT" => "Queretaro",
            "QR" => "Quintana Roo",
            "SL" => "San Luis Potosi",
            "SI" => "Sinaloa",
            "SO" => "Sonora",
            "TB" => "Tabasco",
            "TM" => "Tamaulipas",
            "TL" => "Tlaxcala",
            "VE" => "Veracruz",
            "YU" => "Yucatan",
            "ZA" => "Zacatecas"
        )
    );

    /*
	Useage Example:

		States::display( 'US', create_function('$abbrv,$name', 'return "<option value=\"". $abbrv ."\">". $name ."</option>";') );

	*/
    public static function display($country, $callback, $subset = false)
    {
        // $country is the country to display states from
        // $callback is the anonymous function for formating the data
        // $subset should be an array of ok abbreviations, set to false for all states
        // Returns [true] on success, [false] if no states to display

        if (!is_callable($callback)) {
            throw new Exception('Callback function is not callable.');
        }
        if ($subset && !is_array($subset)) {
            throw new Exception('Subset must be false or an array of acceptable country abbreviations.');
        }

        if (class_exists('Countries') && !Countries::check($country)) {
            return false;
        }
        if (!isset(self::$list[$country])) {
            return false;
        }

        foreach (self::$list[$country] as $abbrv => $name) {
            if ($subset && !in_array($abbrv, $subset)) {
                continue;
            }

            echo $callback($abbrv, $name);
        }

        return true;
    }

    public static function check($country, $stateAbbrv)
    {
        // Check if a state abbrevation is valid
        // Returns [true] if valid, [false] if invalid and [null] if states are not set for this country

        if (class_exists('Countries') && !Countries::check($country)) {
            return false;
        }
        if (!isset(self::$list[$country])) {
            return null;
        }
        if (!isset(self::$list[$country][$stateAbbrv])) {
            return false;
        }

        return true;
    }

    public static function getName($country, $stateAbbrv, $html = true)
    {
        // Get a states's name from its abbrevation
        // Returns the state name [string] if available, [false] if not available

        if (self::check($country, $stateAbbrv)) {
            return false;
        }
        return self::$list[$country][$stateAbbrv];
    }
}
