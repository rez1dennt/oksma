import unittest

from tools.catalog_import.normalize_products import _clean_purpose, extract_specs, normalize_model_name, normalize_value


class ProductNormalizationTests(unittest.TestCase):
    def test_normalizes_units_without_inventing_values(self):
        self.assertEqual(normalize_value('10 куб. м.'), '10 м³')
        self.assertEqual(normalize_value('6 500 кг.'), '6 500 кг')
        self.assertEqual(normalize_model_name('ППТС - 20 марки Оксма'), 'ППТС-20')

    def test_preserves_letter_suffixes_and_decimal_models(self):
        self.assertEqual(normalize_model_name('Оборудование ЗСК-15У полностью укомплектовано'), 'ЗСК-15У')
        self.assertEqual(normalize_model_name('Полуприцеп ПГТС 6,5'), 'ПГТС-6.5')
        self.assertEqual(normalize_model_name('Полуприцеп тракторный ППТС 20П'), 'ППТС-20П')

    def test_removes_sales_copy_without_mistaking_combikorm_for_bank_details(self):
        paragraph = ('Мы предлагаем выгодное ценовое предложение. '
                     'Предназначен для транспортировки сухих комбикормов и загрузки в бункеры.')
        self.assertEqual(
            _clean_purpose([paragraph]),
            'Предназначен для транспортировки сухих комбикормов и загрузки в бункеры.',
        )

    def test_selects_the_matching_model_column_in_wide_tables(self):
        blocks = [{'type': 'table', 'rows': [
            ['№', 'Наименование параметров', 'Модель', 'Модель'],
            ['№', 'Наименование параметров', 'ПЦ-6В', 'ПЦ-20В'],
            ['3', 'Вместимость цистерны, м3, не более', '6,0', '20,0'],
        ]}]
        self.assertEqual(extract_specs(blocks, 'ПЦ-20')['Вместимость цистерны'], '20,0')


if __name__ == '__main__':
    unittest.main()
