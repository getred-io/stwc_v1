<?php
// Funktion zur Berechnung des Osterdatums (Gregorianischer Kalender)
function calculateEaster($year) {
    $a = $year % 19;
    $b = floor($year / 100);
    $c = $year % 100;
    $d = floor($b / 4);
    $e = $b % 4;
    $f = floor(($b + 8) / 25);
    $g = floor(($b - $f + 1) / 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = floor($c / 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = floor(($a + 11 * $h + 22 * $l) / 451);
    $month = floor(($h + $l - 7 * $m + 114) / 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    return "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
}

// Funktion, die alle relevanten Feiertage für ein gegebenes Jahr und Bundesland als assoziatives Array (Datum => Feiertagsname) zurückgibt
function getHolidays($year, $bundesland) {
    $holidays = array();
    // Nationale Feiertage
    $holidays["$year-01-01"] = "Neujahr";

    // Osterbezogene Feiertage
    $easterDate = calculateEaster($year);
    $easter = new DateTime($easterDate);
    // Karfreitag: 2 Tage vor Ostern
    $karfreitag = clone $easter;
    $karfreitag->modify('-2 days');
    $holidays[$karfreitag->format('Y-m-d')] = "Karfreitag";
    // Ostermontag: 1 Tag nach Ostern
    $ostermontag = clone $easter;
    $ostermontag->modify('+1 day');
    $holidays[$ostermontag->format('Y-m-d')] = "Ostermontag";

    // Weitere bundesweite Feiertage
    $holidays["$year-05-01"] = "Tag der Arbeit";
    // Christi Himmelfahrt: 39 Tage nach Ostern
    $himmelfahrt = clone $easter;
    $himmelfahrt->modify('+39 days');
    $holidays[$himmelfahrt->format('Y-m-d')] = "Christi Himmelfahrt";
    // Pfingstmontag: 50 Tage nach Ostern
    $pfingstmontag = clone $easter;
    $pfingstmontag->modify('+50 days');
    $holidays[$pfingstmontag->format('Y-m-d')] = "Pfingstmontag";
    $holidays["$year-10-03"] = "Tag der Deutschen Einheit";
    $holidays["$year-12-25"] = "1. Weihnachtstag";
    $holidays["$year-12-26"] = "2. Weihnachtstag";

    // Staatsspezifische Feiertage:
    // Fronleichnam (in einigen Bundesländern)
    $statesFronleichnam = array("Baden-Württemberg", "Bayern", "Hessen", "Nordrhein-Westfalen", "Rheinland-Pfalz", "Saarland");
    if (in_array($bundesland, $statesFronleichnam)) {
        $fronleichnam = clone $easter;
        $fronleichnam->modify('+60 days');
        $holidays[$fronleichnam->format('Y-m-d')] = "Fronleichnam";
    }
    // Reformationstag (in bestimmten ost- und norddeutschen Bundesländern)
    $statesReformation = array("Brandenburg", "Mecklenburg-Vorpommern", "Sachsen", "Sachsen-Anhalt", "Thüringen");
    if (in_array($bundesland, $statesReformation)) {
        $holidays["$year-10-31"] = "Reformationstag";
    }
    // Allerheiligen (in einigen Bundesländern)
    $statesAllerheiligen = array("Baden-Württemberg", "Bayern", "Nordrhein-Westfalen", "Rheinland-Pfalz", "Saarland");
    if (in_array($bundesland, $statesAllerheiligen)) {
        $holidays["$year-11-01"] = "Allerheiligen";
    }
    // Buß- und Bettag (nur in Sachsen)
    if ($bundesland == "Sachsen") {
        $date = new DateTime("$year-11-23");
        while ($date->format('N') != 3) {
            $date->modify('-1 day');
        }
        $holidays[$date->format('Y-m-d')] = "Buß- und Bettag";
    }
    return $holidays;
}

// Funktion, um die Arbeitstage im Monat (Montag bis Freitag ohne Feiertage) zu zählen
function countWorkingDays($year, $month, $holidays) {
    $workingDays = 0;
    $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($day = 1; $day <= $totalDays; $day++) {
        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
        $date = new DateTime($dateStr);
        if ($date->format('N') < 6) {
            if (!array_key_exists($dateStr, $holidays)) {
                $workingDays++;
            }
        }
    }
    return $workingDays;
}

// Hilfsfunktion, um POST-Werte sicher auszugeben
function getPostValue($key, $default) {
    return isset($_POST[$key]) ? htmlspecialchars($_POST[$key]) : $default;
}

// Definiere das Array der Monatsnamen
$months = array(
    1 => "Januar",
    2 => "Februar",
    3 => "März",
    4 => "April",
    5 => "Mai",
    6 => "Juni",
    7 => "Juli",
    8 => "August",
    9 => "September",
    10 => "Oktober",
    11 => "November",
    12 => "Dezember"
);

// Definiere das Array der Jahre (2025 bis 2035)
$years = range(2025, 2035);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berechnung der zu leistenden Stunden</title>
    <style>
        /* Universeller Box-Sizing-Reset */
        * {
            box-sizing: border-box;
        }
        /* Modernes, responsives Design */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .form-container, .result-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            /* Gleicher Abstand zum Rand der Box */
            padding-left: 20px;
            padding-right: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            flex: 1 1 300px;
        }
        h1 {
            margin-top: 0;
        }
        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        form input[type="number"],
        form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        /* Neue Klassen für nebeneinander angeordnete Felder */
        .input-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .input-group {
            flex: 1;
        }
        form input[type="submit"] {
            background-color: #007BFF;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        form input[type="submit"]:hover {
            background-color: #0056b3;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .input-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Formularbereich -->
        <div class="form-container">
            <h1>Berechnung der zu leistenden Stunden</h1>
            <form method="post">
                <!-- Monat und Jahr nebeneinander, Monat links -->
                <div class="input-row">
                    <div class="input-group">
                        <label for="month">Monat:</label>
                        <select name="month" id="month" required>
                            <?php 
                            $selectedMonth = getPostValue('month', date('n'));
                            foreach ($months as $num => $name) {
                                $selected = ($num == $selectedMonth) ? "selected" : "";
                                echo "<option value=\"$num\" $selected>$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="year">Jahr:</label>
                        <select name="year" id="year" required>
                            <?php 
                            $selectedYear = getPostValue('year', date('Y'));
                            foreach ($years as $yr) {
                                $selected = ($yr == $selectedYear) ? "selected" : "";
                                echo "<option value=\"$yr\" $selected>$yr</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <label for="bundesland">Bundesland:</label>
                <select name="bundesland" id="bundesland" required>
                    <?php 
                    $bundeslaender = array(
                        "Baden-Württemberg", "Bayern", "Berlin", "Brandenburg", "Bremen", "Hamburg", "Hessen",
                        "Mecklenburg-Vorpommern", "Niedersachsen", "Nordrhein-Westfalen", "Rheinland-Pfalz",
                        "Saarland", "Sachsen", "Sachsen-Anhalt", "Schleswig-Holstein", "Thüringen"
                    );
                    $selectedBundesland = getPostValue('bundesland', 'Hessen');
                    foreach ($bundeslaender as $land) {
                        $selected = ($land == $selectedBundesland) ? "selected" : "";
                        echo "<option value=\"$land\" $selected>$land</option>";
                    }
                    ?>
                </select>
                
                <!-- Urlaubstage und Kranktage nebeneinander -->
                <div class="input-row">
                    <div class="input-group">
                        <label for="urlaubstage">Urlaubstage:</label>
                        <input type="number" name="urlaubstage" id="urlaubstage" value="<?php echo getPostValue('urlaubstage', '0'); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="kranktage">Kranktage:</label>
                        <input type="number" name="kranktage" id="kranktage" value="<?php echo getPostValue('kranktage', '0'); ?>" required>
                    </div>
                </div>
                
                <label for="wochenstunden">Wochenarbeitszeit (Stunden):</label>
                <input type="number" name="wochenstunden" id="wochenstunden" value="<?php echo getPostValue('wochenstunden', '40'); ?>" required>
                
                <label for="kurzarbeit">Kurzarbeit in % (z.B. 20 für 20% Kurzarbeit):</label>
                <input type="number" name="kurzarbeit" id="kurzarbeit" value="<?php echo getPostValue('kurzarbeit', '0'); ?>" required>
                
                <input type="submit" value="Berechnen">
            </form>
        </div>
        <!-- Ergebnisbereich -->
        <div class="result-container">
            <?php
            $output = "";
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $year = intval($_POST["year"]);
                $month = intval($_POST["month"]);
                $monthName = isset($months[$month]) ? $months[$month] : $month;
                $bundesland = $_POST["bundesland"];
                $urlaubstage = intval($_POST["urlaubstage"]);
                $kranktage = intval($_POST["kranktage"]);
                $wochenstunden = floatval($_POST["wochenstunden"]);
                $kurzarbeit = floatval($_POST["kurzarbeit"]);

                $dailyHours = $wochenstunden / 5;
                $holidaysAll = getHolidays($year, $bundesland);
                $holidays = array();
                foreach ($holidaysAll as $date => $name) {
                    if (intval(date("n", strtotime($date))) == $month) {
                        $holidays[$date] = $name;
                    }
                }
                $workingDays = countWorkingDays($year, $month, $holidays);
                $effectiveWorkingDays = $workingDays - ($urlaubstage + $kranktage);
                if ($effectiveWorkingDays < 0) { $effectiveWorkingDays = 0; }
                $fullHours = $effectiveWorkingDays * $dailyHours;
                $factor = (100 - $kurzarbeit) / 100;
                $requiredHours = $fullHours * $factor;
                
                $output .= "<h2>Ergebnis für $monthName $year:</h2>";
                $output .= "<p>Im Bundesland <strong>$bundesland</strong> gibt es insgesamt <strong>$workingDays</strong> reguläre Arbeitstage (ohne Feiertage).</p>";
                $output .= "<p>Nach Abzug von <strong>$urlaubstage</strong> Urlaubstagen und <strong>$kranktage</strong> Krankentagen verbleiben <strong>$effectiveWorkingDays</strong> Arbeitstage.</p>";
                $output .= "<p>Bei einer <strong>$wochenstunden</strong>-Stunden-Woche entspricht das ohne Kurzarbeit <strong>" . round($fullHours, 2) . "</strong> Stunden.</p>";
                $output .= "<p>Mit <strong>" . $kurzarbeit . "%</strong> Kurzarbeit müssen Sie <strong>" . round($requiredHours, 2) . "</strong> Stunden leisten.</p>";
                
                $holidayCount = count($holidays);
                if ($holidayCount > 0) {
                    $holidayNames = implode(", ", array_values($holidays));
                    $output .= "<p>Im Monat <strong>$monthName</strong> gibt es <strong>$holidayCount</strong> Feiertag(e): $holidayNames.</p>";
                } else {
                    $output .= "<p>Im Monat <strong>$monthName</strong> gibt es keine Feiertage.</p>";
                }
            } else {
                $year = date('Y');
                $month = date('n');
                $monthName = isset($months[$month]) ? $months[$month] : $month;
                $bundesland = "Hessen";
                $urlaubstage = 0;
                $kranktage = 0;
                $wochenstunden = 40;
                $kurzarbeit = 0;
                $dailyHours = $wochenstunden / 5;

                $holidaysAll = getHolidays($year, $bundesland);
                $holidays = array();
                foreach ($holidaysAll as $date => $name) {
                    if (intval(date("n", strtotime($date))) == $month) {
                        $holidays[$date] = $name;
                    }
                }
                $workingDays = countWorkingDays($year, $month, $holidays);
                $fullHours = $workingDays * $dailyHours;
                
                $output .= "<h2>Standard Ergebnis für $monthName $year:</h2>";
                $output .= "<p>Im Bundesland <strong>$bundesland</strong> gibt es insgesamt <strong>$workingDays</strong> reguläre Arbeitstage (ohne Feiertage).</p>";
                $output .= "<p>Bei einer <strong>$wochenstunden</strong>-Stunden-Woche entsprechen das <strong>" . round($fullHours, 2) . "</strong> regulären Arbeitsstunden.</p>";
                
                $holidayCount = count($holidays);
                if ($holidayCount > 0) {
                    $holidayNames = implode(", ", array_values($holidays));
                    $output .= "<p>Im Monat <strong>$monthName</strong> gibt es <strong>$holidayCount</strong> Feiertag(e): $holidayNames.</p>";
                } else {
                    $output .= "<p>Im Monat <strong>$monthName</strong> gibt es keine Feiertage.</p>";
                }
            }
            echo $output;
            ?>
        </div>
    </div>
</body>
</html>
