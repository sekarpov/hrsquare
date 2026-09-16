import type { Cell } from "../types";
export const nineBoxMetadata: Record<
    Cell,
    { title: string; description: string; recommendation: string | null }
> = {
    M1: {
        title: "НЕРЕАЛИЗОВАННЫЙ ПОТЕНЦИАЛ",
        description:
            "Низкая доказанная результативность при сильных признаках Potential.",
        recommendation: "Рассмотреть другую роль / уровень",
    },
    S1: {
        title: "ТАЛАНТ / БУДУЩИЙ ЛИДЕР",
        description:
            "Результативность подтверждена на хорошем уровне + высокий Potential.",
        recommendation: "Инвестируем в рост — результат уже на хорошем уровне",
    },
    B1: {
        title: "ТАЛАНТ / ЗВЕЗДА",
        description: "Высокая доказанная результативность + высокий Potential.",
        recommendation: "Приоритетный кандидат",
    },
    M2: {
        title: "НЕДОСТАТОЧНАЯ РЕЗУЛЬТАТИВНОСТЬ",
        description: "Результат недостаточно подтверждён.",
        recommendation: null,
    },
    S2: {
        title: "ПЕРСПЕКТИВНЫЙ КАНДИДАТ",
        description: "Результативность подтверждена + Potential развития есть.",
        recommendation: "Решение требует оценки риска",
    },
    B2: {
        title: "СИЛЬНЫЙ ПРОФЕССИОНАЛ",
        description:
            "Высокая результативность + достаточный Potential для текущей / следующей сложности.",
        recommendation: "Сильный кандидат на текущую позицию",
    },
    M3: {
        title: "НЕ ПОДТВЕРЖДЁН",
        description: "Низкая результативность + низкий Potential.",
        recommendation: null,
    },
    S3: {
        title: "ОГРАНИЧЕННЫЙ ПРОГНОЗ",
        description: "Средняя результативность + низкий Potential.",
        recommendation: null,
    },
    B3: {
        title: "СИЛЬНЫЙ ИСПОЛНИТЕЛЬ ТЕКУЩЕГО УРОВНЯ",
        description:
            "Высокая результативность + низкий Potential. Редкое исключение (~5%).",
        recommendation: null,
    },
};
