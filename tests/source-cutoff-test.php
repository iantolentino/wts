<?php
declare(strict_types=1);
require dirname(__DIR__).'/backend/app/feed.php';
$cutoffs=['person@example.com'=>'2026-06-10'];
$ticket=['email'=>'Person@Example.com','date'=>'2026-06-10 23:59:59'];
if (!source_ticket_within_cutoff($ticket,$cutoffs)) throw new RuntimeException('Cutoff date must be inclusive.');
$ticket['date']='2026-06-11 00:00:00';
if (source_ticket_within_cutoff($ticket,$cutoffs)) throw new RuntimeException('Later ticket must be excluded.');
$ticket['date']='2026-02-30';
if (source_ticket_within_cutoff($ticket,$cutoffs)) throw new RuntimeException('Invalid source dates must fail closed.');
try { whittle_feed_verify_cutoffs(['capabilities'=>[],'tickets'=>[]],$cutoffs); throw new RuntimeException('Missing capability accepted.'); }
catch (RuntimeException $e) { if ($e->getMessage()==='Missing capability accepted.') throw $e; }
try { whittle_feed_verify_cutoffs(['capabilities'=>['source_cutoff_v1'],'tickets'=>[$ticket]],$cutoffs); throw new RuntimeException('Excluded ticket accepted.'); }
catch (RuntimeException $e) { if ($e->getMessage()==='Excluded ticket accepted.') throw $e; }
echo "Source cutoff checks passed.\n";
