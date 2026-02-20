<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute қабылдануы керек.',
    'active_url' => ':attribute жарамды URL емес.',
    'after' => ':attribute :date-тен кейінгі күн болуы керек.',
    'after_or_equal' => ':attribute :date-ке тең немесе одан кейінгі күн болуы керек.',
    'alpha' => ':attribute тек әріптерден тұруы мүмкін.',
    'alpha_dash' => ':attribute тек әріптер, сандар, сызықшалар және астын сызулардан тұруы мүмкін.',
    'alpha_num' => ':attribute тек әріптер мен сандардан тұруы мүмкін.',
    'array' => ':attribute массив болуы керек.',
    'before' => ':attribute :date-тен бұрынғы күн болуы керек.',
    'before_or_equal' => ':attribute :date-ке тең немесе одан бұрынғы күн болуы керек.',
    'between' => [
        'numeric' => ':attribute :min мен :max аралығында болуы керек.',
        'file' => ':attribute :min мен :max килобайт аралығында болуы керек.',
        'string' => ':attribute :min мен :max таңба аралығында болуы керек.',
        'array' => ':attribute :min мен :max элемент аралығында болуы керек.',
    ],
    'boolean' => ':attribute өрісі шын немесе жалған болуы керек.',
    'confirmed' => ':attribute растауы сәйкес келмейді.',
    'date' => ':attribute жарамды күн емес.',
    'date_equals' => ':attribute :date-ке тең күн болуы керек.',
    'date_format' => ':attribute :format форматына сәйкес келмейді.',
    'different' => ':attribute және :other әртүрлі болуы керек.',
    'digits' => ':attribute :digits цифрадан тұруы керек.',
    'digits_between' => ':attribute :min мен :max цифра аралығында болуы керек.',
    'dimensions' => ':attribute жарамсыз сурет өлшемдеріне ие.',
    'distinct' => ':attribute өрісінің қайталанған мәні бар.',
    'email' => ':attribute жарамды email мекенжайы болуы керек.',
    'ends_with' => ':attribute келесілердің бірімен аяқталуы керек: :values.',
    'exists' => 'Таңдалған :attribute жарамсыз.',
    'file' => ':attribute файл болуы керек.',
    'filled' => ':attribute өрісінде мән болуы керек.',
    'gt' => [
        'numeric' => ':attribute :value-дан үлкен болуы керек.',
        'file' => ':attribute :value килобайттан үлкен болуы керек.',
        'string' => ':attribute :value таңбадан үлкен болуы керек.',
        'array' => ':attribute :value элементтен көп болуы керек.',
    ],
    'gte' => [
        'numeric' => ':attribute :value-ға тең немесе одан үлкен болуы керек.',
        'file' => ':attribute :value килобайтқа тең немесе одан үлкен болуы керек.',
        'string' => ':attribute :value таңбаға тең немесе одан үлкен болуы керек.',
        'array' => ':attribute :value элементке тең немесе одан көп болуы керек.',
    ],
    'image' => ':attribute сурет болуы керек.',
    'in' => 'Таңдалған :attribute жарамсыз.',
    'in_array' => ':attribute өрісі :other ішінде жоқ.',
    'integer' => ':attribute бүтін сан болуы керек.',
    'ip' => ':attribute жарамды IP мекенжайы болуы керек.',
    'ipv4' => ':attribute жарамды IPv4 мекенжайы болуы керек.',
    'ipv6' => ':attribute жарамды IPv6 мекенжайы болуы керек.',
    'json' => ':attribute жарамды JSON жолы болуы керек.',
    'lt' => [
        'numeric' => ':attribute :value-дан кіші болуы керек.',
        'file' => ':attribute :value килобайттан кіші болуы керек.',
        'string' => ':attribute :value таңбадан кіші болуы керек.',
        'array' => ':attribute :value элементтен аз болуы керек.',
    ],
    'lte' => [
        'numeric' => ':attribute :value-ға тең немесе одан кіші болуы керек.',
        'file' => ':attribute :value килобайтқа тең немесе одан кіші болуы керек.',
        'string' => ':attribute :value таңбаға тең немесе одан кіші болуы керек.',
        'array' => ':attribute :value элементтен көп болмауы керек.',
    ],
    'max' => [
        'numeric' => ':attribute :max-тан үлкен болмауы керек.',
        'file' => ':attribute :max килобайттан үлкен болмауы керек.',
        'string' => ':attribute :max таңбадан үлкен болмауы керек.',
        'array' => ':attribute :max элементтен көп болмауы керек.',
    ],
    'mimes' => ':attribute мына түрдегі файл болуы керек: :values.',
    'mimetypes' => ':attribute мына түрдегі файл болуы керек: :values.',
    'min' => [
        'numeric' => ':attribute кемінде :min болуы керек.',
        'file' => ':attribute кемінде :min килобайт болуы керек.',
        'string' => ':attribute кемінде :min таңба болуы керек.',
        'array' => ':attribute кемінде :min элемент болуы керек.',
    ],
    'not_in' => 'Таңдалған :attribute жарамсыз.',
    'not_regex' => ':attribute форматы жарамсыз.',
    'numeric' => ':attribute сан болуы керек.',
    'password' => 'Құпия сөз қате.',
    'present' => ':attribute өрісі болуы керек.',
    'regex' => ':attribute форматы жарамсыз.',
    'required' => ':attribute өрісі міндетті.',
    'required_if' => ':other :value болғанда :attribute өрісі міндетті.',
    'required_unless' => ':other :values ішінде болмаса :attribute өрісі міндетті.',
    'required_with' => ':values болғанда :attribute өрісі міндетті.',
    'required_with_all' => ':values болғанда :attribute өрісі міндетті.',
    'required_without' => ':values болмаса :attribute өрісі міндетті.',
    'required_without_all' => ':values ешқайсысы болмаса :attribute өрісі міндетті.',
    'same' => ':attribute және :other сәйкес болуы керек.',
    'size' => [
        'numeric' => ':attribute :size болуы керек.',
        'file' => ':attribute :size килобайт болуы керек.',
        'string' => ':attribute :size таңба болуы керек.',
        'array' => ':attribute :size элементтен тұруы керек.',
    ],
    'starts_with' => ':attribute келесілердің бірімен басталуы керек: :values.',
    'string' => ':attribute жол болуы керек.',
    'timezone' => ':attribute жарамды аймақ болуы керек.',
    'unique' => ':attribute бұрын алынған.',
    'uploaded' => ':attribute жүктеу қатесі.',
    'url' => ':attribute форматы жарамсыз.',
    'uuid' => ':attribute жарамды UUID болуы керек.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];

