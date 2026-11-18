/** @type {Set<string>} */
export const CATEGORY_ICON_KEYS = new Set([
    'wallet',
    'award',
    'briefcase',
    'trend-up',
    'plus-circle',
    'utensil',
    'car',
    'cart',
    'building',
    'leisure',
    'health',
    'minus-circle',
    'tag',
    'folder',
]);

/** @type {Record<string, string>} */
export const LEGACY_NAME_TO_ICON = {
    工资: 'wallet',
    奖金: 'award',
    兼职: 'briefcase',
    理财: 'trend-up',
    其他收入: 'plus-circle',
    餐饮: 'utensil',
    交通: 'car',
    购物: 'cart',
    住房: 'building',
    娱乐: 'leisure',
    医疗: 'health',
    其他支出: 'minus-circle',
};

export const PICKABLE_ICONS = [
    'tag',
    'wallet',
    'cart',
    'car',
    'building',
    'utensil',
    'health',
    'leisure',
    'briefcase',
    'trend-up',
    'award',
    'folder',
];

export function resolveCategoryIconKey(icon, name) {
    if (icon && CATEGORY_ICON_KEYS.has(icon)) {
        return icon;
    }
    if (name && LEGACY_NAME_TO_ICON[name]) {
        return LEGACY_NAME_TO_ICON[name];
    }
    return 'tag';
}