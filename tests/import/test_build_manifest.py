import unittest

from tools.catalog_import.build_manifest import classify_name


class ManifestClassificationTests(unittest.TestCase):
    def test_classifies_document_families_and_models(self):
        self.assertEqual(classify_name('КП Бункер ЗСК-15У в сборе.doc'), ('zsk', 'ЗСК-15У'))
        self.assertEqual(classify_name('Коммерческое предложение на ПЦ 11В.docx'), ('pc', 'ПЦ-11В'))
        self.assertEqual(classify_name('КП ППТС 18 марки Оксма.doc'), ('ppts', 'ППТС-18'))
        self.assertEqual(classify_name('КП Прицеп низкорамный.doc'), ('lowbed', 'Низкорамный прицеп'))

    def test_does_not_guess_models_from_generic_image_names(self):
        self.assertEqual(classify_name('IMG_20260520_174021.jpg'), (None, None))


if __name__ == '__main__':
    unittest.main()
