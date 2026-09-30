import type { SectionCardsProps } from "./types";
import styles from "./SectionCards.module.css";
import { cx } from "../../../../utils";

function SectionCards({children, variant}: SectionCardsProps) {
    return (
        <div className={cx(styles, "section-cards", `section-cards--${variant}`)}>
            {children}
        </div>
    )
}

export default SectionCards;