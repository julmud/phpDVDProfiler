<?php

if (!defined('IN_SCRIPT')) {
	die('This script should not be manually executed ... Possible Hacking attempt');
}

// MySQL Layer from phpBB2 - original sources available at http://sf.net
// Id: mysql.php,v 1.2 2003/03/17 23:05:17 psotfx Exp
if (!defined('SQL_LAYER'))
{

define('SQL_LAYER', 'mysql');

class sql_db
{
	var $db_connect_id;
	var $query_result;
	var $return_on_error = false;
	var $transaction = false;
	var $sql_report = '';
	var $sql_time = 0;

	//
	// Constructor
	//
	function __construct($sqlserver, $sqluser, $sqlpassword, $database, $port = false, $persistency = false, $DebugSQL=false, $bequiet=false)
	{
		$this->open_queries = array();
		$this->ErrorArray = array('message' => '', 'code' => 0);
		$this->num_queries = 0;
		$this->sql_list = '';
		$this->DebugSQL = $DebugSQL;

		$this->persistency = $persistency;
		$this->user = $sqluser;
		$this->password = $sqlpassword;
		$this->server = $sqlserver . (($port) ? ':' . $port : '');
		$this->dbname = $database;

		$this->db_connect_id = ($this->persistency) ? @mysql_pconnect($this->server, $this->user, $this->password) : @mysql_connect($this->server, $this->user, $this->password);
		if ($this->db_connect_id && $this->dbname != '')
		{
			mysql_set_charset("latin1", $this->db_connect_id);
			if (@mysql_select_db($this->dbname))
			{
				return $this->db_connect_id;
			}
		}

		if ($bequiet) {
			$this->ErrorArray['message'] =  @mysql_error();
			$this->ErrorArray['code'] =  @mysql_errno();
			return;
		}
		$this->sql_error('');
	}

	function GetErrorState() {
		return($this->ErrorArray);
	}

	//
	// Other base methods
	//
	function sql_close()
	{
		if (!$this->db_connect_id)
		{
			return false;
		}

		if (count($this->open_queries))
		{
			foreach ($this->open_queries as $query_id)
			{
				@mysql_free_result($query_id);
			}
		}

		return @mysql_close($this->db_connect_id);
	}

	function sql_return_on_error($fail = false)
	{
		$this->return_on_error = $fail;
	}

	function sql_ret_queries()
	{
		return $this->sql_list;
	}

	function sql_num_queries()
	{
		return $this->num_queries;
	}

	function sql_transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				$this->transaction = true;
				$result = mysql_query('BEGIN', $this->db_connect_id);
				break;

			case 'commit':
				$this->transaction = false;
				$result = mysql_query('COMMIT', $this->db_connect_id);
				break;

			case 'rollback':
				$this->transaction = false;
				$result = mysql_query('ROLLBACK', $this->db_connect_id);
				break;

			default:
				$result = true;
		}

		return $result;
	}

	// Base query method
	function sql_query($query = '', $expire_time = 0, $quiet = false)
	{
		// print $query; return true;
		if ($query != '')
		{
			global $cache;

			if (!$expire_time || !$cache->sql_load($query, $expire_time))
			{
				if ($expire_time)
				{
					$cache_result = true;
				}

				$this->query_result = false;
				$this->num_queries++;
				if ($this->DebugSQL)
					$this->sql_list .= "\n\t$query";

				if (!empty($_GET['explain']))
				{
					global $starttime;

					$curtime = explode(' ', microtime());
					$curtime = $curtime[0] + $curtime[1] - $starttime;
				}

				if (!$this->query_result =mysql_query($query, $this->db_connect_id))
				{
					if (!$quiet) {
						$this->sql_error($query);
						return false;
					}
				}

				if (!empty($_GET['explain']))
				{
					$endtime = explode(' ', microtime());
					$endtime = $endtime[0] + $endtime[1] - $starttime;

					$this->sql_report .= "<pre>Query:\t" . htmlspecialchars(preg_replace('/[\s]*[\n\r\t]+[\n\r\s\t]*/', "\n\t", $query), ENT_COMPAT, 'ISO-8859-1') . "\n\n";

					if ($this->query_result)
					{
						$this->sql_report .= "Time before:  $curtime\nTime after:   $endtime\nElapsed time: <b>" . ($endtime - $curtime) . "</b>\n</pre>";
					}
					else
					{
						$error = $this->sql_error();
						$this->sql_report .= '<b>FAILED</b> - MySQL Error ' . $error['code'] . ': ' . htmlspecialchars($error['message'], ENT_COMPAT, 'ISO-8859-1') . '<br><br><pre>';
					}

					$this->sql_time += $endtime - $curtime;

					if (preg_match('/^SELECT/', $query))
					{
						$html_table = FALSE;
						if ($result = mysql_query("EXPLAIN $query", $this->db_connect_id))
						{
							while ($row = mysql_fetch_assoc($result))
							{
								if (!$html_table && count($row))
								{
									$html_table = TRUE;
									$this->sql_report .= "<table width=100% border=1 cellpadding=2 cellspacing=1>\n";
									$this->sql_report .= "<tr>\n<td><b>" . implode("</b></td>\n<td><b>", array_keys($row)) . "</b></td>\n</tr>\n";
								}
								$this->sql_report .= "<tr>\n<td>" . implode("&nbsp;</td>\n<td>", array_values($row)) . "&nbsp;</td>\n</tr>\n";
							}
						}

						if ($html_table)
						{
							$this->sql_report .= '</table><br>';
						}
					}

					$this->sql_report .= "<hr>\n";
				}
// This is cacheing of query results, and is not really implemented properly ...
//				$this->open_queries[] = $this->query_result;
			}

			if (!empty($cache_result))
			{
				$cache->sql_save($query, $this->query_result);
				@mysql_free_result(array_pop($this->open_queries));
			}
		}
		else
		{
			return false;
		}

		return ($this->query_result) ? $this->query_result : false;
	}

	// 20030406 Ashe: switched up $total and $offset as per MySQL manual
	function sql_query_limit($query, $total, $offset = 0, $expire_time = 0)
	{
		if ($query != '')
		{
			$this->query_result = false;
			$this->num_queries++;
			if ($this->DebugSQL)
				$this->sql_list .= "\n\t$query";

			// if $total is set to 0 we do not want to limit the number of rows
			if ($total == 0)
			{
				$total = -1;
			}

			$query .= ' LIMIT ' . ((!empty($offset)) ? $offset . ', ' . $total : $total);

			return $this->sql_query($query, $expire_time);
		}
		else
		{
			return false;
		}
	}

	// Idea for this from Ikonboard
	function sql_build_array($query, $assoc_ary = false)
	{
		if (!is_array($assoc_ary))
		{
			return false;
		}

		$fields = array();
		$values = array();
		if ($query == 'INSERT')
		{
			foreach ($assoc_ary as $key => $var)
			{
				$fields[] = $key;

				if (is_null($var))
				{
					$values[] = 'NULL';
				}
				elseif (is_string($var))
				{
					$values[] = "'" . $this->sql_escape($var) . "'";
				}
				else
				{
					$values[] = (is_bool($var)) ? intval($var) : $var;
				}
			}

			$query = ' (' . implode(',', $fields) . ') VALUES (' . implode(',',$values) . ')';
		}
		else if ($query == 'UPDATE')
		{
			$values = array();
			foreach ($assoc_ary as $key => $var)
			{
				if (is_null($var))
				{
					$values[] = "$key = NULL";
				}
				elseif (is_string($var))
				{
					$values[] = "$key = '" . $this->sql_escape($var) . "'";
				}
				else
				{
					$values[] = (is_bool($var)) ? "$key = " . intval($var) : "$key = $var";
				}
			}
			$query = implode(', ', $values);
		}

		return $query;
	}

	// Other query methods
	//
	// NOTE :: Want to remove _ALL_ reliance on sql_numrows from core code ...
	//         don't want this here by a middle Milestone
	function sql_numrows($query_id = false)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}

		return ($query_id) ? @mysql_num_rows($query_id) : false;
	}

	function sql_affectedrows()
	{
		return ($this->db_connect_id) ? @mysql_affected_rows($this->db_connect_id) : false;
	}

	function sql_fetchrow($query_id = 0)
	{
		global $cache;

		if (!$query_id)
		{
			$query_id = $this->query_result;
		}

		/*
		if ($cache->sql_exists($query_id))
		{
			return $cache->sql_fetchrow($query_id);
		}
		*/

		return ($query_id) ? @mysql_fetch_assoc($query_id) : false;
	}

	function sql_fetchrowset($query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		if ($query_id)
		{
			unset($this->rowset[$query_id]);
			unset($this->row[$query_id]);
			while ($this->rowset[$query_id] = $this->sql_fetchrow($query_id))
			{
				$result[] = $this->rowset[$query_id];
			}
			return $result;
		}
		return false;
	}

	function sql_fetchfield($field, $rownum = -1, $query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		if ($query_id)
		{
			if ($rownum > -1)
			{
				$result = @mysql_result($query_id, $rownum, $field);
			}
			else
			{
				if (empty($this->row[$query_id]) && empty($this->rowset[$query_id]))
				{
					if ($this->sql_fetchrow())
					{
						$result = $this->row[$query_id][$field];
					}
				}
				else
				{
					if ($this->rowset[$query_id])
					{
						$result = $this->rowset[$query_id][$field];
					}
					elseif ($this->row[$query_id])
					{
						$result = $this->row[$query_id][$field];
					}
				}
			}
			return $result;
		}
		return false;
	}

	function sql_rowseek($rownum, $query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}

		return ($query_id) ? @mysql_data_seek($query_id, $rownum) : false;
	}

	function sql_fetch_array($res)
	{
		return ($res) ? @mysql_fetch_array($res) : false;
	}

	function sql_nextid()
	{
		return ($this->db_connect_id) ? @mysql_insert_id($this->db_connect_id) : false;
	}

	function sql_freeresult($query_id = false)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}

		return ($query_id) ? @mysql_free_result($query_id) : false;
	}

	function sql_escape($msg)
	{
		return mysql_real_escape_string(stripslashes($msg));
	}

	function sql_error($sql = '')
	{
		$result = array(
			'message' => @mysql_error(),
			'code' => @mysql_errno()
		);

		if (!$this->return_on_error)
		{
			if ($this->transaction)
			{
				$this->sql_transaction('rollback');
			}

			$the_query = '';
			if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) $the_query = $_SERVER['QUERY_STRING'];
			if (empty($the_query) && isset($_ENV['QUERY_STRING'])) $the_query = $_ENV['QUERY_STRING'];

			$this_page = (!empty($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : $_ENV['PHP_SELF'];
			$this_page .= '&' . $the_query;

			$message = '<u>SQL ERROR</u> [ ' . SQL_LAYER . ' ]<br /><br />' . mysql_error() . '<br /><br /><u>CALLING PAGE</u><br /><br />'  . $this_page . (($sql != '') ? '<br /><br /><u>SQL</u><br /><br />' . $sql : '') . '<br />';
			trigger_error($message, E_USER_ERROR);
		}


		return $result;
	}

} // class sql_db

} // if ... define
