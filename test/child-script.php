<?php
/**
 * Simple script that doing nothing but keep running for some seconds.
 * @author Eder Jani Martins <zetared@gmail.com>
 */
 
 /*
 * Get a random number from 10 to 50 and use in sleep time
 */
$number = 10 * rand( 1, 5);
echo "\nProcess Starting: $number\n";
/*
 * Random time to wait in sleep
 */
sleep($number);
echo "\nProcess Finishing: $number\n";