<?php
/**
 * Class Backup
 *
 * Can be called using $this->load->model('tool/backup');
 *
 * @package Admin\Model\Tool
 */
class ModelToolBackup extends Model {
	/**
	 * Get Tables
	 *
	 * @return array<int, string>
	 *
	 * @example
	 *
	 * $this->load->model('tool/backup');
	 *
	 * $results = $this->model_tool_backup->getTables();
	 */
	public function getTables(): array {
		$table_data = [];

		$query = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "`");

		foreach ($query->rows as $result) {
			$table = reset($result);

			if ($table && oc_substr($table, 0, strlen(DB_PREFIX)) == DB_PREFIX) {
				$table_data[] = $table;
			}
		}

		return $table_data;
	}

	/**
	 * Get Records
	 *
	 * @param string $table
	 * @param int    $start
	 * @param int    $limit
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @example
	 *
	 * $this->load->model('tool/backup');
	 *
	 * $results = $this->model_tool_backup->getRecords($table, $start, $limit);
	 */
	public function getRecords(string $table, int $start = 0, int $limit = 100): array {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT * FROM `" . $table . "` LIMIT " . (int)$start . "," . (int)$limit);

		if ($query->num_rows) {
			return $query->rows;
		} else {
			return [];
		}
	}

	/**
	 * Get Total Records
	 *
	 * @param string $table
	 *
	 * @return int
	 *
	 * @example
	 *
	 * $this->load->model('tool/backup');
	 *
	 * $record_total = $this->model_tool_backup->getTotalRecords($table);
	 */
	public function getTotalRecords(string $table): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . $table . "`");

		if ($query->num_rows) {
			return (int)$query->row['total'];
		} else {
			return 0;
		}
	}

	/**
	 * Backup
	 *
	 * @param array<string, mixed> $tables
	 *
	 * @return string
	 *
	 * @example
	 *
	 * $this->load->model('tool/backup');
	 *
	 * $this->response->setOutput($this->model_tool_backup->backup($tables));
	 */
	public function backup(array $tables): string {
		$output = '';

		foreach ($tables as $table) {
			if (DB_PREFIX) {
				if (!str_contains($table, DB_PREFIX)) {
					$status = false;
				} else {
					$status = true;
				}
			} else {
				$status = true;
			}

			if ($status) {
				$output .= 'TRUNCATE TABLE `' . $table . '`;' . "\n\n";

				$query = $this->db->query("SELECT * FROM `" . $table . "`");

				foreach ($query->rows as $result) {
					$fields = '';

					foreach (array_keys($result) as $value) {
						$fields .= '`' . $value . '`, ';
					}

					$values = '';

					foreach (array_values($result) as $value) {
						$value = str_replace(["\x00", "\x0a", "\x0d", "\x1a"], ['\0', '\n', '\r', '\Z'], $value);
						$value = str_replace(["\n", "\r", "\t"], ['\n', '\r', '\t'], $value);
						$value = str_replace('\\', '\\\\', $value);
						$value = str_replace('\'', '\\\'', $value);
						$value = str_replace('\\\n', '\n', $value);
						$value = str_replace('\\\r', '\r', $value);
						$value = str_replace('\\\t', '\t', $value);

						$values .= '\'' . $value . '\', ';
					}

					$output .= 'INSERT INTO `' . $table . '` (' . preg_replace('/, $/', '', $fields) . ') VALUES (' . preg_replace('/, $/', '', $values) . ');' . "\n";
				}

				$output .= "\n\n";
			}
		}

		return $output;
	}
}
