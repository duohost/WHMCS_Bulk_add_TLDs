<?php
/*
    WHMCS Bulk TLD adder v0.1.0 - adds TLD pricing to WHMCS in bulk
    Copyright (C) 2016  Samuel Craven

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    */
require_once('../configuration.php');

$conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_username, $db_password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$TLD_create=$conn->prepare("INSERT INTO tbldomainpricing (`extension`, `dnsmanagement`, `emailforwarding`, `idprotection`, `eppcode`, `autoreg`, `order`)
	VALUES (:tld,'','','','','','0')");
$TLD_get_id=$conn->prepare("SELECT `id` from `tbldomainpricing`
	WHERE `extension` = :tld");
$price_insert=$conn->prepare("INSERT INTO tblpricing (`type`, `currency`, `relid`, `msetupfee`, `qsetupfee`, `ssetupfee`, `asetupfee`, `bsetupfee`, `tsetupfee`, `monthly`, `quarterly`, `semiannually`, `annually`, `biennially`, `triennially`)
	VALUES (:type, '1', :tld_id, :one, :two, :three, :four, :five, '0.00', :six, :seven, :eight, :nine, :ten, '0.00')");

$input = array_map('str_getcsv', file('tlds.csv'));
foreach ($input as $line) {

	//tld,reg,transfer,renew,minYr,MaxYr
	$tld 			= $line[0];
	$reg_price		= $line[1];
	$transfer_price	= $line[2];
	$renew_price	= $line[3];
	$min_year		= $line[4];
	$max_year		= $line[5];

	//Check if TLD exists. If not, create it and get its ID.
	$TLD_get_id->bindParam(':tld', $tld);
	$TLD_get_id->execute();
	$result = $TLD_get_id->fetch();
	$tld_id = $result[id];
	if (!tld_id) {
		//Create the TLD in tbldomainpricing
		$TLD_create->bindParam(':tld', $tld);
		$TLD_create->execute();

		//GET TLD'S ID AND ASSIGN IT TO $tld_id
		$TLD_get_id->bindParam(':tld', $tld);
		$TLD_get_id->execute();
		$result = $TLD_get_id->fetch();
		$tld_id = $result[id];
	}
	//Create an array we'll loop for rather than writing nearly identical code 3 times
	$type_array = [	["domainregister", $reg_price],
					["domaintransfer", $transfer_price],
					["domainrenew", $renew_price] ];

	foreach ($type_array as $entry) {
		$type = $entry[0];
		$per_year = $entry[1];
		//Generate array for price
		$price_array = [];
		for($i=1; $i<=10; $i++) {
			if ($i < $min_year || $i > $max_year) {
				$price_array[$i] = -1;
			}
			else {
				$price_array[$i] = $per_year * $i;
			}
		}
		$price_insert->bindParam(':type', $type);
		$price_insert->bindParam(':tld_id', $tld_id);
		$price_insert->bindParam(':one', $price_array[1]);
		$price_insert->bindParam(':two', $price_array[2]);
		$price_insert->bindParam(':three', $price_array[3]);
		$price_insert->bindParam(':four', $price_array[4]);
		$price_insert->bindParam(':five', $price_array[5]);
		$price_insert->bindParam(':six', $price_array[6]);
		$price_insert->bindParam(':seven', $price_array[7]);
		$price_insert->bindParam(':eight', $price_array[8]);
		$price_insert->bindParam(':nine', $price_array[9]);
		$price_insert->bindParam(':ten', $price_array[10]);
		$price_insert->execute();

		echo "Added" . $tld . "'s " . $type . "values at " . $per_year . " per year.\n";
	}
}
echo "Completed.\n";
?>
