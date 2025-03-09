<?php
// This file simulates the system being at March 8, 2025 at 22:00 Brasilia time

// Set the default timezone to Brasilia
date_default_timezone_set('America/Sao_Paulo');

// Target date: March 8, 2025 at 22:00 Brasilia time
$targetDate = '2025-03-08 22:00:00';
$targetTimestamp = strtotime($targetDate);

// Store the time difference to apply to all date functions
$timeDiff = $targetTimestamp - time();

// Override PHP's date and time functions
function time() {
    global $timeDiff;
    return \time() + $timeDiff;
}

function date($format, $timestamp = null) {
    global $timeDiff;
    if ($timestamp === null) {
        $timestamp = \time() + $timeDiff;
    } else {
        $timestamp += $timeDiff;
    }
    return \date($format, $timestamp);
}

function strtotime($time, $now = null) {
    global $timeDiff;
    if ($now === null) {
        $now = \time() + $timeDiff;
    } else {
        $now += $timeDiff;
    }
    return \strtotime($time, $now);
}

function gmdate($format, $timestamp = null) {
    global $timeDiff;
    if ($timestamp === null) {
        $timestamp = \time() + $timeDiff;
    } else {
        $timestamp += $timeDiff;
    }
    return \gmdate($format, $timestamp);
}

function getdate($timestamp = null) {
    global $timeDiff;
    if ($timestamp === null) {
        $timestamp = \time() + $timeDiff;
    } else {
        $timestamp += $timeDiff;
    }
    return \getdate($timestamp);
}

function mktime($hour, $minute, $second, $month, $day, $year) {
    return \mktime($hour, $minute, $second, $month, $day, $year);
}

function gmmktime($hour, $minute, $second, $month, $day, $year) {
    return \gmmktime($hour, $minute, $second, $month, $day, $year);
}

function microtime($get_as_float = null) {
    global $timeDiff;
    $microtime = \microtime($get_as_float);
    if ($get_as_float) {
        return $microtime + $timeDiff;
    }
    list($usec, $sec) = explode(' ', $microtime);
    return $usec . ' ' . ($sec + $timeDiff);
}

// Display a message indicating the time travel is active
echo '<div style="background-color: #ffeb3b; color: #000; padding: 10px; text-align: center; font-weight: bold;">';
echo 'MODO SIMULAÇÃO: Sistema configurado para ' . date('d/m/Y H:i:s') . ' (Horário de Brasília)';
echo '</div>';