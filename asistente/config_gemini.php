<?php
/**
 * Configuracion de Gemini API para ADA.
 * La API Key vive solo en variables de entorno; nunca se envia a JavaScript.
 */

require_once __DIR__ . '/../config/env.php';

define('GEMINI_API_KEY', env_value('GEMINI_API_KEY', ''));
define('GEMINI_MODEL', env_value('GEMINI_MODEL', 'gemini-2.0-flash'));
define('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY);
