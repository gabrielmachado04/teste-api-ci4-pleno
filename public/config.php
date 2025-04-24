<?php
if (class_exists('Redis')) {
    echo "Redis está disponível!";
} else {
    echo "Redis não está disponível.";
}
?>