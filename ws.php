<?php
header('Content-Type: application/json; charset=utf-8');

$user = isset($_REQUEST['user']) ? trim((string) $_REQUEST['user']) : '';
$otp = isset($_REQUEST['otp']) ? trim((string) $_REQUEST['otp']) : '';

if ($user === '' || $otp === '') {
	http_response_code(400);
	echo json_encode([
		'ok' => false,
		'code' => 400,
		'message' => 'Parametros incompletos: user y otp son requeridos.'
	]);
	exit;
}

$multiotpPath = __DIR__ . DIRECTORY_SEPARATOR . 'multiotp.exe';
if (!file_exists($multiotpPath)) {
	http_response_code(500);
	echo json_encode([
		'ok' => false,
		'code' => 500,
		'message' => 'No se encontro multiotp.exe en el servidor.'
	]);
	exit;
}

$command = escapeshellarg($multiotpPath) . ' ' . escapeshellarg($user) . ' ' . escapeshellarg($otp);
$output = [];
$exitCode = 1;

exec($command . ' 2>&1', $output, $exitCode);

echo json_encode([
	'ok' => ($exitCode === 0),
	'code' => $exitCode,
	'message' => ($exitCode === 0) ? 'OTP valido' : 'OTP invalido o error de validacion',
	'raw' => trim(implode("\n", $output))
]);
?>