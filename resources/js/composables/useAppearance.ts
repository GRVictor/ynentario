import { ref, computed } from 'vue';

export type Theme = 'light' | 'dark' | 'system';
export type FontSize = 'small' | 'normal' | 'large' | 'huge';

const THEME_KEY = 'ynentario_theme';
const FONT_SIZE_KEY = 'ynentario_font_size';

const fontSizesMap: Record<FontSize, { label: string; percent: string; description: string }> = {
    small: { label: 'Compacto', percent: '90%', description: 'Mayor densidad de información (90%)' },
    normal: { label: 'Estándar', percent: '100%', description: 'Tamaño predeterminado recomendado (100%)' },
    large: { label: 'Grande', percent: '112.5%', description: 'Mayor legibilidad y confort (112.5%)' },
    huge: { label: 'Muy grande', percent: '125%', description: 'Máxima accesibilidad visual (125%)' },
};

// Estado reactivo singleton en toda la aplicación
const currentTheme = ref<Theme>('system');
const currentFontSize = ref<FontSize>('normal');
const isSystemDark = ref<boolean>(false);
let isInitialized = false;

export function useAppearance() {
    const initAppearance = () => {
        if (typeof window === 'undefined' || isInitialized) return;

        // Cargar tema guardado
        const savedTheme = localStorage.getItem(THEME_KEY) as Theme | null;
        if (savedTheme && ['light', 'dark', 'system'].includes(savedTheme)) {
            currentTheme.value = savedTheme;
        } else {
            currentTheme.value = 'system';
        }

        // Cargar tamaño de fuente guardado
        const savedFontSize = localStorage.getItem(FONT_SIZE_KEY) as FontSize | null;
        if (savedFontSize && savedFontSize in fontSizesMap) {
            currentFontSize.value = savedFontSize;
        } else {
            currentFontSize.value = 'normal';
        }

        // Escuchar cambios en el modo oscuro del sistema operativo
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        isSystemDark.value = mediaQuery.matches;

        mediaQuery.addEventListener('change', (e) => {
            isSystemDark.value = e.matches;
            if (currentTheme.value === 'system') {
                applyTheme();
            }
        });

        applyTheme();
        applyFontSize();
        isInitialized = true;
    };

    const isDark = computed<boolean>(() => {
        if (currentTheme.value === 'dark') return true;
        if (currentTheme.value === 'light') return false;
        return isSystemDark.value;
    });

    const applyTheme = () => {
        if (typeof document === 'undefined') return;
        const html = document.documentElement;
        if (isDark.value) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
    };

    const applyFontSize = () => {
        if (typeof document === 'undefined') return;
        const fontConfig = fontSizesMap[currentFontSize.value] || fontSizesMap.normal;
        document.documentElement.style.fontSize = fontConfig.percent;
    };

    const setTheme = (theme: Theme) => {
        currentTheme.value = theme;
        if (typeof window !== 'undefined') {
            localStorage.setItem(THEME_KEY, theme);
        }
        applyTheme();
    };

    const toggleDark = () => {
        if (isDark.value) {
            setTheme('light');
        } else {
            setTheme('dark');
        }
    };

    const setFontSize = (size: FontSize) => {
        if (!(size in fontSizesMap)) return;
        currentFontSize.value = size;
        if (typeof window !== 'undefined') {
            localStorage.setItem(FONT_SIZE_KEY, size);
        }
        applyFontSize();
    };

    return {
        theme: currentTheme,
        fontSize: currentFontSize,
        isDark,
        fontSizesMap,
        initAppearance,
        setTheme,
        toggleDark,
        setFontSize,
    };
}
