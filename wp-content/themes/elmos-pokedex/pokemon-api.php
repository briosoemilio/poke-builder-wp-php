<?php

add_action('rest_api_init', function () {
  register_rest_route('pokemon/v1', '/list', [
    'methods' => 'GET',
    'callback' => function ($request) {
      // GET PARAMS
      $limit = $request->get_param('limit') ?: 50;
      $page = $request->get_param('page') ?: 1;

      // Call the function to get the Pokémon list
      return get_pokemon_list($limit, $page);
    },
  ]);
});

add_action('rest_api_init', function () {
  register_rest_route('pokemon/v1', '/data/(?P<identifier>[a-zA-Z0-9-]+)', [
    'methods' => 'GET',
    'callback' => function ($request) {
      // GET PARAMS
      $identifier = $request['identifier'];

      // Call the function to get the Pokémon data
      return get_pokemon_data($identifier);
    },
  ]);
});

add_action('rest_api_init', function () {
  register_rest_route('ability/v1', '/data/(?P<abilityName>[a-zA-Z0-9-]+)', [
    'methods' => 'GET',
    'callback' => function ($request) {
      // GET PARAMS
      $abilityName = $request['abilityName'];

      // Call the function to get the Pokémon data
      return get_pokemon_ability_data($abilityName);
    },
  ]);
});

add_action('rest_api_init', function () {
  register_rest_route('pokemon/v1', '/add-team', [
    'methods' => 'POST',
    'callback' => function ($request) {
      global $wpdb;

      // Call the function to create the table if it doesn't exist
      create_pokemon_team_table();

      // Get POST params
      $name = sanitize_text_field($request->get_param('name'));
      $nickname = sanitize_text_field($request->get_param('nickname'));
      $stats = sanitize_text_field($request->get_param('stats')); // Should be a JSON string
      $ability = sanitize_text_field($request->get_param('ability'));
      $held_item = sanitize_text_field($request->get_param('held_item'));

      // Add the Pokémon team to the database
      $result = add_pokemon_team($name, $nickname, $stats, $ability, $held_item);

      if ($result === false) {
        return new WP_Error('db_insert_error', 'Failed to insert Pokémon team into the database', ['status' => 500]);
      }

      return ['message' => 'Pokémon team added successfully', 'data' => $result];
    },
    'permission_callback' => '__return_true',
  ]);
});

add_action('rest_api_init', function () {
  register_rest_route('pokemon_team/v1', '/all', [
    'methods' => 'GET',
    'callback' => function () {
      // Call the function to get all Pokémon teams
      $all_teams = get_all_pokemon_teams();

      // Return the result or a message if no teams are found
      if ($all_teams) {
        return rest_ensure_response($all_teams);
      } else {
        return rest_ensure_response(['message' => 'No Pokémon teams found']);
      }
    },
  ]);
});