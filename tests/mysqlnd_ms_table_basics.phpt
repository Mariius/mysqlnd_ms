--TEST--
table filter basics
--SKIPIF--
<?php
require_once('skipif.inc');
require_once("connect.inc");

_skipif_check_extensions(array("mysqli"));
_skipif_check_feature(array("table_filter"));
_skipif_connect($master_host_only, $user, $passwd, $db, $master_port, $master_socket);
_skipif_connect($slave_host_only, $user, $passwd, $db, $slave_port, $slave_socket);

$settings = array(
	"myapp" => array(
		'master' => array(
			"master1" => array(
				'host' 		=> $master_host_only,
				'port' 		=> (int)$master_port,
				'socket' 	=> $master_socket,
			),
		),

		'slave' => array(
			"slave1" => array(
				'host' 	=> $slave_host_only,
				'port' 	=> (int)$slave_port,
				'socket' => $slave_socket,
			),
		 ),

		'lazy_connections' => 1,
		'filters' => array(
			"table" => array(
				"rules" => array(
					$db . ".test%" => array(
						"master" => array("master2"),
						"slave" => array("slave1"),
					),
					"%"	=> array(
						"master" => array("master1"),
						"slave"	 => array("master1"),
					)
				),
			),

			"random" => array(),
		),
	),

);
if ($error = mst_create_config("test_mysqlnd_ms_table_basics.ini", $settings))
	die(sprintf("SKIP %s\n", $error));
?>
--INI--
mysqlnd_ms.enable=1
mysqlnd_ms.config_file=test_mysqlnd_ms_table_basics.ini
--FILE--
<?php
	require_once("connect.inc");
	require_once("util.inc");

	/* shall use host = forced_master_hostname_abstract_name from the ini file */
	$link = mst_mysqli_connect("myapp", $user, $passwd, $db, $port, $socket);
	if (mysqli_connect_errno()) {
		printf("[002] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());
	}

	/* db.ulf -> master 1 */
	mst_mysqli_verbose_query(3, $link, "DROP TABLE IF EXISTS ulf");

	/* db.test1 -> master 2 -> no such host */
	mst_mysqli_verbose_query(4, $link, "DROP TABLE IF EXISTS test1");

	print "done!";
?>
--CLEAN--
<?php
	require_once("connect.inc");

	if (!unlink("test_mysqlnd_ms_table_basics.ini"))
	  printf("[clean] Cannot unlink ini file 'test_mysqlnd_ms_table_basics.ini'.\n");
?>
--EXPECTF--
[003 + 01] Query 'DROP TABLE IF EXISTS ulf'
[003 + 02] Thread '%d'
[004 + 01] Query 'DROP TABLE IF EXISTS test1'

Warning: mysqli::query(): (mysqlnd_ms) Couldn't find the appropriate master connection. Something is wrong in %s on line %d
[004] [2000] (mysqlnd_ms) Couldn't find the appropriate master connection. Something is wrong
[004 + 02] Thread '%d'
done!