<?php

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/ProductionInwardService.php';

function assertInwardScanTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectInwardScanFailure(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (InvalidArgumentException $exception) {
        return;
    }
    throw new RuntimeException($message);
}

$expected = [
    'PIECE001' => ['item_id' => 11, 'variant_id' => 21],
    'PIECE002' => ['item_id' => 11, 'variant_id' => 21],
    'PIECE003' => ['item_id' => 12, 'variant_id' => 22],
];
$completeScans = [
    ['tag' => 'piece001|SKU-1', 'condition' => 'ACCEPTED'],
    ['tag' => '{"tag_code":"PIECE002"}', 'condition' => 'DAMAGED'],
    ['tag' => 'piece003', 'condition' => 'ACCEPTED'],
];
$validated = ProductionInwardService::validateConcernBatchScans($expected, $completeScans);
assertInwardScanTest(count($validated) === 3, 'A complete set of scans must be accepted.');
assertInwardScanTest($validated['PIECE002']['condition'] === 'DAMAGED', 'Damaged condition must be preserved.');

expectInwardScanFailure(
    static fn() => ProductionInwardService::validateConcernBatchScans($expected, [
        $completeScans[0], $completeScans[0], $completeScans[2],
    ]),
    'Duplicate scans must be rejected.'
);
expectInwardScanFailure(
    static fn() => ProductionInwardService::validateConcernBatchScans($expected, [
        $completeScans[0], $completeScans[1], ['tag' => 'NOT-IN-BATCH', 'condition' => 'ACCEPTED'],
    ]),
    'A product from another batch must be rejected.'
);
$partial = ProductionInwardService::validateConcernBatchScans($expected, [$completeScans[0]]);
assertInwardScanTest(count($partial) === 1, 'Partial delivery scans must be accepted so missing pieces can be reported.');

$extraTags = ['PIECE004' => ['item_id' => 11, 'variant_id' => 21]];
$withExtra = ProductionInwardService::validateConcernBatchScans(
    $expected,
    [$completeScans[0], ['tag' => 'PIECE004', 'condition' => 'ACCEPTED']],
    $extraTags
);
assertInwardScanTest($withExtra['PIECE004']['is_excess'] === true, 'A verified QR outside the dispatched set must be identified as excess.');

$decoded = ProductionInwardService::decodePieceCodeList('["piece001|SKU-1", {"tag_code":"PIECE002"}, "piece001"]');
assertInwardScanTest($decoded === ['PIECE001', 'PIECE002'], 'Piece code decoding must normalize payloads and deduplicate tags.');
assertInwardScanTest(
    ProductionInwardService::decodePieceCodeList('PIECE003, piece004|SKU-2') === ['PIECE003', 'PIECE004'],
    'Comma-delimited piece codes must normalize scanned tags cleanly.'
);

echo "PASS: concern inward scans allow short receipts, identify verified excess pieces, and reject duplicates and unknown codes.\n";
