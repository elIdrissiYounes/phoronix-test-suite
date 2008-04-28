<?php

require("pts-core/functions/pts-functions.php");
require("pts-core/functions/pts-functions-extra.php");

$COMMAND = $argv[1];

if(isset($argv[2]))
	$ARG_1 = $argv[2];

if(isset($argv[3]))
	$ARG_2 = $argv[3];

if(isset($argv[4]))
	$ARG_3 = $argv[4];

switch($COMMAND)
{
	case "LIST_SAVED_RESULTS":
		echo pts_string_header("Phoronix Test Suite - Saved Results");
		foreach(glob(SAVE_RESULTS_DIR . "*/composite.xml") as $benchmark_file)
		{
			$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
			$title = $xml_parser->getXMLValue("PhoronixTestSuite/Suite/Title");
			$suite = $xml_parser->getXMLValue("PhoronixTestSuite/Suite/Name");
			$raw_results = $xml_parser->getXMLArrayValues("PhoronixTestSuite/Benchmark/Results");
			$results_xml = new tandem_XmlReader($raw_results[0]);
			$identifiers = $results_xml->getXMLArrayValues("Group/Entry/Identifier");
			$saved_identifier = array_pop(explode('/', dirname($benchmark_file)));

			if(!empty($title))
			{
				echo $title . "\n";
				printf("Saved Name: %-18ls Test: %-18ls \n", $saved_identifier, $suite);

				foreach($identifiers as $id)
					echo "\t- $id\n";

				echo "\n";
			}
		}
		break;
	case "FORCE_INSTALL_BENCHMARK":
	case "INSTALL_BENCHMARK":
		require("pts-core/functions/pts-functions-install.php");

		if(empty($ARG_1))
		{
			echo "\nThe benchmark or suite name to install must be supplied.\n";
			exit;
		}

		if($COMMAND == "FORCE_INSTALL_BENCHMARK")
			define("PTS_FORCE_INSTALL", 1);

		$ARG_1 = strtolower($ARG_1);

		// Any external dependencies?
		pts_install_package_on_distribution($ARG_1);

		if(defined("PTS_MANUAL_SUPPORT"))
		{
			pts_bool_question("These dependencies should be installed before proceeding as one or more benchmarks could fail. Press any key when you're ready to continue");
		}

		// Install benchmarks
		$install_objects = "";
		pts_recurse_install_benchmark($ARG_1, $install_objects);
		break;
	case "INSTALL_EXTERNAL_DEPENDENCIES":
		require("pts-core/functions/pts-functions-install.php");

		if(empty($ARG_1))
		{
			echo "\nThe benchmark or suite name to install external dependencies for must be supplied.\n";
			exit;
		}

		if($ARG_1 == "phoronix-test-suite" || $ARG_1 == "pts" || $ARG_1 == "trondheim-pts")
		{
			$pts_dependencies = array("php-gd", "php-extras", "build-utilities");
			$packages_to_install = array();
			$continue_install = pts_package_generic_to_distro_name($packages_to_install, $pts_dependencies);

			if($continue_install)
				pts_install_packages_on_distribution_process($packages_to_install);
		}
		else
			pts_install_package_on_distribution($ARG_1);
		break;
	case "LIST_TESTS":
		echo pts_string_header("Phoronix Test Suite - Tests");
		foreach(glob(XML_PROFILE_DIR . "*.xml") as $benchmark_file)
		{
		 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
			$name = $xml_parser->getXMLValue("PTSBenchmark/Information/Title");
			$identifier = basename($benchmark_file, ".xml");
			$license = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/License");
			$status = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/Status");

			printf("%-18ls - %-30ls [Status: %s, License: %s]\n", $identifier, $name, $status, $license);
		}
		echo "\n";
		break;
	case "LIST_SUITES":
		echo pts_string_header("Phoronix Test Suite - Suites");
		$benchmark_suites = array();
		foreach(glob(XML_SUITE_DIR . "*.xml") as $benchmark_file)
		{
		 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
			$name = $xml_parser->getXMLValue("PTSuite/PhoronixTestSuite/Title");
			$benchmark_type = $xml_parser->getXMLValue("PTSuite/PhoronixTestSuite/BenchmarkType");
			$identifier = basename($benchmark_file, ".xml");

			printf("%-16ls - %-32ls [Type: %s]\n", $identifier, $name, $benchmark_type);
		}
		echo "\n";
		break;
	case "SUITE_INFO":
		if(pts_benchmark_type($ARG_1) == "TEST_SUITE")
		{
			$xml_parser = new tandem_XmlReader(file_get_contents(XML_SUITE_DIR . $ARG_1 . ".xml"));
			$tests_in_suite = $xml_parser->getXMLArrayValues("PTSuite/PTSBenchmark/Benchmark");
			$suite_name = $xml_parser->getXMLValue("PTSuite/PhoronixTestSuite/Title");
			$suite_maintainer = $xml_parser->getXMLValue("PTSuite/PhoronixTestSuite/Maintainer");
			$suite_version = $xml_parser->getXMLValue("PTSuite/PhoronixTestSuite/Version");
			$suite_type = $xml_parser->getXMLValue("PTSuite/PhoronixTestSuite/BenchmarkType");
			$total_tests = count($tests_in_suite);
			$tests_in_suite = array_unique($tests_in_suite);
			$unique_tests = count($tests_in_suite);

			echo pts_string_header($suite_name . " (" . $ARG_1 . " v" . $suite_version . ")");

			echo "Maintainer: " . $suite_maintainer . "\n";
			echo "Suite Type: " . $suite_type . "\n";
			echo "Total Tests: " . $total_tests . "\n";
			echo "Unique Tests: " . $unique_tests . "\n";
			echo "\n";

			foreach($tests_in_suite as $test)
			{
				$benchmark_file = XML_PROFILE_DIR . $test . ".xml";

			 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
				$name = $xml_parser->getXMLValue("PTSBenchmark/Information/Title");
				$identifier = basename($benchmark_file, ".xml");
				$license = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/License");
				$status = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/Status");

				printf("%-18ls - %-30ls [Status: %s, License: %s]\n", $identifier, $name, $status, $license);
			}
		
			echo "\n";
		}
		else
		{
			echo "\n$ARG_1 is not a test suite.\n";
		}
		break;
	case "TEST_INFO":
		if(pts_benchmark_type($ARG_1) == "BENCHMARK")
		{
			$xml_parser = new tandem_XmlReader(file_get_contents(XML_PROFILE_DIR . $ARG_1 . ".xml"));

			$test_title = $xml_parser->getXMLValue("PTSBenchmark/Information/Title");

			$test_version = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/Version");
			$test_type = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/BenchmarkType");
			$test_app_type = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/ApplicationType");
			$test_license = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/License");
			$test_status = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/Status");
			$test_maintainer = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/Maintainer");

			echo pts_string_header($test_title . " (" . $ARG_1 . " v" . $test_version . ")");

			echo "Maintainer: " . $test_maintainer . "\n";
			echo "Test Type: " . $test_type . "\n";
			echo "Software Type: " . $test_app_type . "\n";
			echo "License Type: " . $test_license . "\n";
			echo "Test Status: " . $test_status . "\n";
		
			echo "\n";
		}
		else
		{
			echo "\n$ARG_1 is not a test.\n";
		}
		break;
	case "SHOW_RESULT":
		if(is_file(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml"))
			$URL = SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml";
		//else if(trim(@file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=" . $ARG_1)) == "REMOTE_FILE")
		//	$URL = "http://global.phoronix-test-suite.com/index.php?k=profile&u=" . trim($ARG_1);
		else
			$URL = false;

		if($URL != FALSE)
			shell_exec("./pts/launch-browser.sh $URL &");
		else
			echo "\n$ARG_1 was not found.\n";
		break;
	case "UPLOAD_RESULT":

		if(is_file($ARG_1))
			$USE_FILE = $ARG_1;
		else if(is_file(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml"))
			$USE_FILE = SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml";
		else
		{
			echo "\nThis result doesn't exist!\n";
			exit(0);
		}

		$upload_url = pts_global_upload_result($USE_FILE);

		if(!empty($upload_url))
			echo "Results Uploaded To: " . $upload_url . "\n\n";
		break;
	case "REMOVE_ALL_RESULTS":
		$remove_all = pts_bool_question("Are you sure you wish to remove all saved results (Y/n)?", true);

		if($remove_all)
		{
			foreach(glob(SAVE_RESULTS_DIR . "*/composite.xml") as $benchmark_file)
			{
				$saved_identifier = array_pop(explode('/', dirname($benchmark_file)));
				pts_remove_saved_result($saved_identifier);
			}
		}
		break;
	case "REMOVE_RESULT":
		if(is_file(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml"))
		{
			echo "\n";
			pts_remove_saved_result($ARG_1);
		}
		else
			echo "\nThis result doesn't exist!\n";
		break;
	case "SYS_INFO":
		echo pts_string_header("Phoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n" . "System Information");
		echo "Hardware:\n" . pts_hw_string() . "\n\n";
		echo "Software:\n" . pts_sw_string() . "\n\n";
		break;
	case "MERGE_RESULTS":
		require("pts-core/functions/pts-functions-merge.php");

		$BASE_FILE = $ARG_1;
		$MERGE_FROM_FILE = $ARG_2;
		$MERGE_TO = $ARG_3;

		if(empty($BASE_FILE) || empty($MERGE_FROM_FILE))
		{
			echo "\nTwo saved result profile names must be supplied.\n";
			exit;
		}

		if(empty($MERGE_TO))
			$MERGE_TO = $OLD_RESULTS;

		$BASE_FILE = pts_find_file($BASE_FILE);
		$MERGE_FROM_FILE = pts_find_file($MERGE_FROM_FILE);

		if(empty($MERGE_TO))
		{
			do
			{
				$rand_file = rand(1000, 9999);
				$MERGE_TO = "merge-$rand_file/";
			}while(is_dir(SAVE_RESULTS_DIR . $MERGE_TO));

			$MERGE_TO .= "composite.xml";
		}

		// Merge Results
		$MERGED_RESULTS = pts_merge_benchmarks(file_get_contents($BASE_FILE), file_get_contents($MERGE_FROM_FILE));
		pts_save_result($MERGE_TO, $MERGED_RESULTS);
		display_web_browser(SAVE_RESULTS_DIR . $MERGE_TO);
		break;
	case "DIAGNOSTICS_DUMP":
		echo pts_string_header("Phoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n" . "Diagnostics Dump");
		$pts_defined_constants = get_defined_constants(true);
			foreach($pts_defined_constants["user"] as $constant => $constant_value)
				echo $constant . " = " . $constant_value . "\n";

			echo "\nEnvironmental Variables (accessible via test scripts):\n";
			foreach(pts_env_variables() as $var => $var_value)
				echo $var . " = " . $var_value . "\n";
			echo "\n";
		break;
	case "INITIAL_CONFIG":
		if(is_file(PTS_USER_DIR . "user-config.xml"))
		{
			copy(PTS_USER_DIR . "user-config.xml", PTS_USER_DIR . "user-config.xml.old");
			unlink(PTS_USER_DIR . "user-config.xml");
		}
		pts_user_config_init();
		break;
	case "LOGIN":
		echo "\nIf you haven't already registered for your free PTS Global account, you can do so at http://global.phoronix-test-suite.com/\n\nOnce you have registered your account and clicked the link within the verification email, enter your log-in information below.\n\n";
		echo "User-Name: ";
		$username = trim(strtolower(fgets(STDIN)));
		echo "Password: ";
		$password = md5(trim(strtolower(fgets(STDIN))));
		$uploadkey = @file_get_contents("http://www.phoronix-test-suite.com/global/account-verify.php?user_name=$username&user_md5_pass=$password");

		if(!empty($uploadkey))
		{
			pts_user_config_init($username, $uploadkey);
			echo "\nAccount: $uploadkey\nAccount information written to user-config.xml.\n\n";
		}
		else
			echo "\nPTS Global Account Not Found.\n";
		break;
	case "VERSION":
		echo "\nPhoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n\n";
		break;
	default:
		echo "Phoronix Test Suite: Internal Error. Command Not Recognized ($COMMAND).\n";
}

?>
