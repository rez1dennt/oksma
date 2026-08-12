import unittest
from pathlib import Path

from tools.catalog_import.build_php_catalog import load_public_exclusions, public_products


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


if __name__ == '__main__':
    unittest.main()
