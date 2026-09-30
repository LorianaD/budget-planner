// import { Button } from "../../Button";
import type { SectionTopbarProps } from "./types";
import style from './SectionTopbar.module.css';

function getSectionTopbarClassName(variant: string) {
    return `${style.topbar} section-${variant}`;
}

function SectionTopbar({ variant, title, description }: SectionTopbarProps) {
    return (
        <div className={getSectionTopbarClassName(variant)}>
            <div className={style.text}>
                {/* <div>
                    Badge
                </div> */}
                <h2 className={style.title}>
                    {title}
                </h2>
                {description && (
                    <p className={style.description}>
                        {description}
                    </p>
                )}
            </div>

            <div>
                {/* {btnLabel && (
                    <Button type="button" isLoading={false} loadingLabel="Chargement..." >
                        {btnLabel}
                    </Button>
                )}     */}
            </div>
        </div>
    )
}

export default SectionTopbar;