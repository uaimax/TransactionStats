<?php
// Armazena um valor na memória compartilhada
apcu_store('test_key', 'test_value');

// Recupera o valor da memória compartilhada
$value = apcu_fetch('test_key');

echo "The value stored in APCu is: " . $value;
?>
