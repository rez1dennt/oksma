import unittest
from pathlib import Path

from tools.catalog_import.build_php_catalog import (
    load_public_exclusions,
    public_dimensions,
    public_products,
    public_purpose,
    public_seo_description,
)


class PublicCatalogBuildTests(unittest.TestCase):
    def test_excludes_removed_ppts_20_but_keeps_ppts_20p(self):
        exclusions = load_public_exclusions(
            Path('tools/catalog_import/public_exclusions.json')
        )
        products = [
            {'model': 'ППТС-20'},
            {'model': 'ППТС-20П'},
            {'model': 'ППТС-18'},
        ]

        self.assertEqual(
            ['ППТС-20П', 'ППТС-18'],
            [item['model'] for item in public_products(products, exclusions)],
        )

    def test_feed_loaders_omit_duplicate_dimensions_and_keep_seed_drill_purpose(self):
        dimensions = {'Длина': '5 000 мм'}

        self.assertEqual({}, public_dimensions('zsk', dimensions))
        self.assertEqual({}, public_dimensions('pzk', dimensions))
        self.assertEqual(dimensions, public_dimensions('ppts', dimensions))

        purpose = 'Перевозка сухих комбикормов и загрузка наружных бункеров.'
        self.assertIn('зерна', public_purpose('zsk', purpose))
        self.assertIn('сеялок', public_purpose('zsk', purpose))
        self.assertEqual(purpose, public_purpose('ppts', purpose))
        self.assertNotIn('размер', public_seo_description('zsk', 'ЗСК-15'))
        self.assertIn('размер', public_seo_description('ppts', 'ППТС-15'))


if __name__ == '__main__':
    unittest.main()
