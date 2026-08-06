<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/bootstrap.php';

test('document registry exposes two complete public declarations', function (): void {
    truthy(function_exists('document_registry'));
    truthy(function_exists('document_integrity_errors'));
    same(2, count(all_documents()));
    same('ЕАЭС N RU Д-RU.РА04.В.69139/26', find_document('feed-trailers-2026')['registration_number']);
    same('31.05.2031', format_document_date(find_document('feed-trailers-2026')['valid_until']));
    same('ЕАЭС N RU Д-RU.РА05.В.72423/26', find_document('tractor-trailers-2026')['registration_number']);
});

test('document lookup requires explicit product identifiers', function (): void {
    same(['feed-trailers-2026'], array_column(documents_for_product([
        'document_ids' => ['feed-trailers-2026'],
    ]), 'id'));
    same([], documents_for_product(['category' => 'zagruzchiki-suhih-kormov', 'name' => 'ЗСК-10']));
    same([], documents_for_product(['document_ids' => ['unknown-document']]));
});

test('document registry and public PDF assets pass integrity checks', function (): void {
    same([], document_integrity_errors(dirname(__DIR__, 2)));
});
