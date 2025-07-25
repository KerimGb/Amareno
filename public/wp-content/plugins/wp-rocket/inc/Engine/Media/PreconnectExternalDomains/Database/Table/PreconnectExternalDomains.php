<?php
declare(strict_types=1);

namespace WP_Rocket\Engine\Media\PreconnectExternalDomains\Database\Table;

use WP_Rocket\Engine\Common\PerformanceHints\Database\Table\AbstractTable;

class PreconnectExternalDomains extends AbstractTable {
	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $name = 'wpr_preconnect_external_domains';

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @var string
	 */
	protected $db_version_key = 'wpr_preconnect_external_domains_version';

	/**
	 * Database version
	 *
	 * @var int
	 */
	protected $version = 20250217;

	/**
	 * Key => value array of versions => methods.
	 *
	 * @var array
	 */
	protected $upgrades = [];

	/**
	 * Table schema data.
	 *
	 * @var   string
	 */
	protected $schema_data = "
			id               bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			url              varchar(2000)       NOT NULL default '',
			is_mobile        tinyint(1)          NOT NULL default 0,
			domains          longtext            default '',
			error_message    longtext            default '',
			status           varchar(255)        NOT NULL default '',
			modified         timestamp           NOT NULL default '0000-00-00 00:00:00',
			last_accessed    timestamp           NOT NULL default '0000-00-00 00:00:00',
			created_at       timestamp           NULL,
			PRIMARY KEY (id),
			KEY url (url(150), is_mobile),
			KEY modified (modified),
			KEY last_accessed (last_accessed),
			INDEX `status_index` (`status`(191))";
}
