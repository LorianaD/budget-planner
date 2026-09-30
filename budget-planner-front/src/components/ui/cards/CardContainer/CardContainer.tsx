import { cx } from "../../../../utils";
import type { CardsContainerProps } from "./types";
import styles from "./CardContainer.module.css";

function CardContainer({title, children}: CardsContainerProps) {
    return (
        <div className={cx(styles, "card-container")}>
            <h3>
                {title}
            </h3>

            {children}
        </div>
    )
}

export default CardContainer;