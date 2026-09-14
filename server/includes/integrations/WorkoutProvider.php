<?php
declare(strict_types=1);
interface WorkoutProvider {
    public function source(): string;
    /** Stream normalized events: ['type'=>'updated','workout'=>...] or ['type'=>'deleted','id'=>...]. */
    public function events(?string $since): iterable;
}
