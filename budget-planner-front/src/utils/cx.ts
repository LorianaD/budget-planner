// src/utils/cx.ts
export function cx(styles: Record<string, string>, ...classNames: (string | undefined | false)[]) {
    return classNames
        .filter(Boolean)
        .map((name) => styles[name as string] ?? name)
        .join(" ");
}