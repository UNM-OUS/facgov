<?php
$noun = $package->noun();

if ($hiatus = $noun->hiatus()) {
    echo $hiatus->infoCard();
}