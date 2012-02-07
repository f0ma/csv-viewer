<?php
$GLOBALS[accountReport][search] = "accountusage-";

function runReport() {
	$finalRet = array();

	foreach (getFilelist($GLOBALS[csv_path], ".csv") as $file) {
		if (strrpos($file, $GLOBALS[accountReport][search]) > -1) {
			$tmpRet = processFile($file);
			$finalRet = array_merge($finalRet, $tmpRet);
		}
	}
	return $finalRet;
}

function processFile($file) {
	$ret = null;
	$archiveDb = "";
	$accountDb = "";

	$serverName = str_replace(".csv", "", str_replace($GLOBALS[accountReport][search], "", $file));

	foreach (loadCsv($file) as $row) {
		$colCnt = 0;
		list($user, $domain) = split("@", $row[0]);
		if (strrpos($domain, ".archive") > 0) {
			$tmpUser = substr($user, 0, strrpos($user, "-"));
			$tmpDomain = substr($domain, 0, strrpos($domain, ".archive"));
			$archiveDb[$tmpDomain][$tmpUser][mbarchive] = $row[1];
		} else {
			$accountDb[$domain][$user][mbused] = $row[1];
			$accountDb[$domain][$user][mbquota] = $row[2];
			$accountDb[$domain][$user][percentquota] = round(100 / $row[2] * $row[1]);
			$accountDb[$domain][$user][accstatus] = str_replace(" account)", "", str_replace("(", "", $row[3]));
		}
	}	

	$rowCnt = 0;
	foreach ($accountDb as $domain => $domainData) {
		$domainMbused = 0;
		$domainMbquota = 0;
		$domainMbarchive = 0;
		foreach ($domainData as $user => $userData) {
			$ret[$rowCnt][0] = $domain;
			$ret[$rowCnt][1] = $user;
			$ret[$rowCnt][2] = $userData[mbused];
			$ret[$rowCnt][3] = $userData[mbquota];
			$ret[$rowCnt][4] = $userData[percentquota];

			if ($archiveDb[$domain][$user][mbarchive] == "") {
				$ret[$rowCnt][5] = "0";
			} else {
				$ret[$rowCnt][5] = $archiveDb[$domain][$user][mbarchive];
			}

			$domainMbused = $domainMbused + $ret[$rowCnt][2];
			$domainMbquota = $domainMbquota + $ret[$rowCnt][3];
			$domainMbarchive = $domainMbarchive + $ret[$rowCnt][4];
			
			$ret[$rowCnt][6] = $ret[$rowCnt][2] + $ret[$rowCnt][4];
			$ret[$rowCnt][7] = $userData[accstatus];

			$rowCnt++;
		}

		$ret[$rowCnt][0] = $domain;
		$ret[$rowCnt][1] = "on " . $serverName;
		$ret[$rowCnt][2] = $domainMbused;
		$ret[$rowCnt][3] = $domainMbquota;
		$ret[$rowCnt][4] = round(100 / $domainMbquota * $domainMbused);
		$ret[$rowCnt][5] = $domainMbarchive;
		$ret[$rowCnt][6] = $domainMbused + $domainMbarchive;
		$ret[$rowCnt][7] = "Total";

		$rowCnt++;
	}
	return $ret;
}


?>
