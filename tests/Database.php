<?php

class Database
{
    /**
     * Этот запрос всегда выполняется очень долго
     */
    public function reallyLongTime()
    {
        // результат запроса будет возвращаться в виде массива $results
        $results = array(
            array(1, 'test', 'foo value')
        );

        sleep(100);

        return $results;
    }
}
