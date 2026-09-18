<?php
$files = glob("resources/views/pages/settings/*profile*.blade.php");
if(!empty($files)){
    $file = $files[0];
    $content = file_get_contents($file);

    $content = str_replace(
        "public string \$email = '';", 
        "public string \$email = '';\n    public string \$student_code = '';\n    public string \$school = '';\n    public string \$major = '';", 
        $content
    );

    $content = str_replace(
        "\$this->email = Auth::user()->email;", 
        "\$this->email = Auth::user()->email;\n        \$student = Auth::user()->student;\n        if (\$student) {\n            \$this->student_code = \$student->student_code;\n            \$this->school = \$student->school;\n            \$this->major = \$student->major;\n        }", 
        $content
    );

    $content = str_replace(
        "\$user->save();", 
        "\$user->save();\n\n        \$student = \$user->student;\n        if (\$student) {\n            \$student->update([\n                'student_code' => \$this->student_code,\n                'school' => \$this->school,\n                'major' => \$this->major,\n            ]);\n        }", 
        $content
    );

    $content = str_replace(
        "type=\"email\" required autocomplete=\"email\" />",
        "type=\"email\" required autocomplete=\"email\" disabled />",
        $content
    );

    $studentInputs = "            <flux:input wire:model=\"student_code\" :label=\"__('NIM / NISN')\" type=\"text\" required />\n            <flux:input wire:model=\"school\" :label=\"__('Asal Sekolah / Kampus')\" type=\"text\" required />\n            <flux:input wire:model=\"major\" :label=\"__('Jurusan')\" type=\"text\" required />";

    $content = str_replace(
        "<div>\n                <flux:input wire:model=\"email\"",
        $studentInputs . "\n\n            <div>\n                <flux:input wire:model=\"email\"",
        $content
    );

    $content = str_replace(
        ":subheading=\"__('Update your name and email address')\"",
        ":subheading=\"__('Perbarui data profil dan akun Anda')\"",
        $content
    );

    $content = str_replace(
        ":heading=\"__('Profile')\"",
        ":heading=\"__('Profil')\"",
        $content
    );

    file_put_contents($file, $content);
    echo "Profile updated!\n";
}

$filesToRename = [
    'resources/views/components/desktop-user-menu.blade.php',
    'resources/views/layouts/app/sidebar.blade.php',
    'resources/views/partials/settings-heading.blade.php'
];

foreach($filesToRename as $f) {
    if(file_exists($f)){
        $c = file_get_contents($f);
        $c = str_replace("{{ __('Settings') }}", "Profil", $c);
        file_put_contents($f, $c);
    }
}
echo "Sidebar updated!\n";
?>
