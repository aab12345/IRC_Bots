<?php
// Source: https://github.com/Hammster/php-irc-bot
// Modified for SASL PLAIN.
// TODO:
//      Deal with Nick in use.
//      Deal with SASL FAILURE (903)
// https://ircv3.net/specs/extensions/sasl-3.1



// Prevent PHP from stopping the script after 30 sec
set_time_limit(0);

// Change these values!
$channel  = '#channel';
$nickname = 'username';
$password = 'password';

// Opening the socket to the network
$socket = fsockopen("irc.freenode.net", 6667);

fputs($socket, "CAP LS\n");

// Force an endless while
while (1) {
    // Continue the rest of the script here
    while ($data = fgets($socket, 128)) {
        echo $data;
        flush();

        // Separate all data
        $ex = explode(' ', $data);

        // SASL plain witchcraft. Run at own risk. I don't know PHP.
        if ($ex[1] == "CAP" && $ex[3] == "LS") {
            echo "LOL\n";
            fputs($socket, "NICK " . $nickname . "\n");
            fputs($socket, "USER " . $nickname . " 0 * :" . $master . "'s Bot\n");
            fputs($socket, "CAP REQ :sasl\n");

        }
        // :irc.foobar.net CAP HammsterBot ACK :sasl
        if ($ex[1] == "CAP" && $ex[3] == "ACK") {
            // Why does this not work above??
            // && $ex[4] == ":sasl\n") {
            fputs($socket, "AUTHENTICATE PLAIN\n");
        }
        if ($ex[0] ==  "AUTHENTICATE") {
            // Create Password in Base64 - printf 'username\0username\0password' | mmencode 
            fputs($socket, "AUTHENTICATE Your_Base_64_Password_Here_For_SASL_AUTH\n");
        }
        if ($ex[1] == "903") {
            fputs($socket, "CAP END\n");
            fputs($socket, "JOIN $channel\n");
        }
        // End of SASL Witchcraft.

        // Send PONG back to the server
        if ($ex[0] == "PING") {
            fputs($socket, "PONG " . $ex[1] . "\n");
        }

        // executes chat command
        if ($ex[0] != 'PING' && ISSET($ex[3])) {
            $command = str_replace(array(
                chr(10),
                chr(13)
            ), '', $ex[3]);
			
			if ($command == ":!news") {
				$content = file_get_contents("https://newsapi.org/v2/top-headlines?country=gb&apiKey=YOUR_API_KEY_HERE");
				$result  = json_decode($content);
				$random_el = rand(0, count($result->articles) - 1);
			fputs($socket, "PRIVMSG " . $channel . " :" . " [Latest UK News] " . " → " . $result->articles[$random_el]->url . " \n");
			}
        }
    }
}
?>
