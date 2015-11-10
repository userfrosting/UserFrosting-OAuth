<?php

/*
{{name}} - Dymamic markers which are replaced at run time by the relevant index.
*/
// Need a hook to add messages to the language files, or we can create 

$lang = array_merge($lang, array(
	"OAUTH_NOTCONNECTED" => "Your {{provider}} account is not linked to a local account.",
));

return $lang;
