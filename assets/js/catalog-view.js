export const CATALOG_VIEW_KEY = 'oksma.catalogView';

function validView(value) {
  return value === 'list' ? 'list' : 'grid';
}

export function readCatalogView(storage = globalThis.localStorage) {
  try {
    return validView(storage?.getItem(CATALOG_VIEW_KEY));
  } catch {
    return 'grid';
  }
}

export function saveCatalogView(value, storage = globalThis.localStorage) {
  const view = validView(value);
  try {
    storage?.setItem(CATALOG_VIEW_KEY, view);
  } catch {
    // Private browsing and strict browser policies can disable storage.
  }
  return view;
}
