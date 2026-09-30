import type { SectionProps } from "./types";
import style from './SectionContainer.module.css';

function getSectionClassName(variant: string) {
    return `${style.section} section-${variant}`;
}

function Section({ variant, children }: SectionProps) {
    return (
        <section className={getSectionClassName(variant)}>
            {children}
        </section>
    )
}

export default Section;