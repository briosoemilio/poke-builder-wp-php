<?php
require_once get_template_directory() . '/pokemon-api.php';

/**
 * This function returns a list of Pokémon.
 *
 * @param int $limit The number of Pokémon to return (default is 50).
 * @param int $page The page number to return (default is 1).
 * @return array An array of Pokémon data.
 */
function get_pokemon_list($limit = 50, $page = 1)
{
	$offset = ($page - 1) * $limit;
	$url = "https://pokeapi.co/api/v2/pokemon?limit={$limit}&offset={$offset}";

	$response = wp_remote_get($url);

	if (is_wp_error($response)) {
		return [];
	}

	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);

	return $data;
}

/**
 * Get specific Pokémon details by name or number.
 *
 * @param string|int $identifier The Pokémon name or number.
 * @return array|null Array containing Pokémon details or false on failure.
 */
function get_pokemon_data($identifier)
{
	// Determine if the identifier is numeric (ID) or a string (name)
	$url = is_numeric($identifier)
		? "https://pokeapi.co/api/v2/pokemon/{$identifier}"
		: "https://pokeapi.co/api/v2/pokemon/" . strtolower($identifier);

	// Make the API request
	$response = wp_remote_get($url);

	if (is_wp_error($response)) {
		return false; // Return false if the request failed
	}

	// Decode the response body
	$body = wp_remote_retrieve_body($response);
	$pokemon_data = json_decode($body, true);

	if (empty($pokemon_data)) {
		return null; // Return false if no data is found
	}

	// Return the necessary details: name, number, sprite
	return [
		'name' => ucfirst($pokemon_data['name']),
		'number' => $pokemon_data['id'],
		'sprite' => $pokemon_data['sprites']['other']['showdown']['front_default'],
		'list_sprite' => $pokemon_data['sprites']['front_default'],
		'types' => array_map(function ($type) {
			return $type['type']['name'];
		}, $pokemon_data['types']),
		'cries' => $pokemon_data['cries']['legacy'],
		'stats' => $pokemon_data['stats'],
		'abilities' => $pokemon_data['abilities'],
		'moves' => $pokemon_data['moves'],
	];
}

/**
 * Get specific Pokémon ability details by name.
 *
 * @param string $abilityName The ability name.
 * @return array|null Array containing ability details or false on failure.
 */
function get_pokemon_ability_data($abilityName)
{
	// Make the API request
	$url = "https://pokeapi.co/api/v2/ability/" . strtolower($abilityName);
	$response = wp_remote_get($url);

	if (is_wp_error($response)) {
		return null;
	}

	// Decode the response body
	$body = wp_remote_retrieve_body($response);
	$ability_data = json_decode($body, true);

	if (empty($ability_data)) {
		return null; // Return null if no data is found
	}

	$effectEntry = null;

	foreach ($ability_data['effect_entries'] as $entry) {
		if ($entry['language']['name'] === 'en') {
			$effectEntry = $entry;
			break;
		}
	}

	// Return the necessary details: name and effect
	return $effectEntry ? [
		'effect' => $effectEntry['effect'],
		'short_effect' => $effectEntry['short_effect'],
	] : null;
}

/**
 * Create the Pokémon team table in the WordPress database.
 *
 * This function sets up the 'pokemon_team' table with fields for the team ID, Pokémon name,
 * stats, ability, held item, and timestamps for record creation and updates.
 *
 * @return void
 */
function create_pokemon_team_table()
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'pokemon_team';

	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// SQL query to create the table
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id BIGINT NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			nickname VARCHAR(100) NOT NULL,
			stats LONGTEXT NOT NULL,
			ability VARCHAR(100) NOT NULL,
			held_item VARCHAR(100) NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NULL,
			PRIMARY KEY (id)
	) $charset_collate;";

		// Include the upgrade function for dbDelta
		dbDelta($sql);
	}
}

/**
 * Add a Pokémon team to the pokemon_team table.
 *
 * @param string $name The name of the Pokémon.
 * @param string $stats JSON encoded string of the Pokémon's stats.
 * @param string $ability The Pokémon's ability.
 * @param string $held_item The item held by the Pokémon (optional).
 * @return bool|int False on failure, number of rows affected on success.
 */
function add_pokemon_team($name, $nickname, $stats, $ability, $held_item = null)
{
	global $wpdb;
	$table_name = $wpdb->prefix . 'pokemon_team';

	// Insert data into the table
	$result = $wpdb->insert(
		$table_name,
		[
			'name' => $name,
			'nickname' => $nickname,
			'stats' => $stats,
			'ability' => $ability,
			'held_item' => $held_item,
			'created_at' => current_time('mysql')
		],
		['%s', '%s', '%s', '%s', '%s', '%s'] // Define data types
	);

	return $result;
}

/**
 * Get all Pokémon teams from the pokemon_team table.
 *
 * @return array|null Array containing all rows from the pokemon_team table or null if no rows are found.
 */
function get_all_pokemon_teams()
{
	global $wpdb;

	$table_name = $wpdb->prefix . 'pokemon_team';
	$results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

	return $results ? $results : null;
}