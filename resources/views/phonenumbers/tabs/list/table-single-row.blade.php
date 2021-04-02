<tr>

    <td>{!! !empty($pPhoneNumber['extension']) ? \App\Helpers\Helper::format_phonenumber($pPhoneNumber['extension']) : '' !!}</td>
    <td>{!! $pPhoneNumber['description'] !!}</td>
    <td>{!! $pPhoneNumber['destination'] !!}</td>
    <td class="text-center">
        <a
                title="Edit"
                class="btn waves-effect waves-light btn-sm btn-info font-14"
                href="phonenumber/edit/{!! Crypt::encrypt($pPhoneNumber['extension']) !!}"
        >
            <i class="fa fa-edit"></i> Edit
        </a>
    </td>
</tr>
