@section('content')
    <table border="1">
        <tr>
            　
            <td>id</td>
            <td>Title</td>
            <td>Category</td>
            <td>User</td>
            <td>Price</td>
            <td>上架日期</td>
            <td>下架日期</td>
            <td>狀態</td>
            <td>購買</td>

        </tr>
        @for ($i = 0; $i < count($data); $i++)
            <tr>
                <td>{{$data[$i]->id}}</td>
                　
                <td>
                    <a href="{{action('ProductController@getItem', ['id'=>$data[$i]->id])}}"> {{$data[$i]->	product_name}} </a>
                </td>
                　
                <td>{{$data[$i]->product_type}}</td>
                <td>{{$data[$i]->getUserName()}}</td>
                <td>{{$data[$i]->price}}元</td>
                　
                <td>{{$data[$i]->start_date}}</td>
                　
                <td>{{$data[$i]->end_date}}</td>
                <td>{{$data[$i]->GetState()}}</td>
                <td>
                    <button onclick="Buy('{{$data[$i]->id}}')">購買</button>
                </td>
            </tr>
        @endfor
    </table>
    {{ $data->links() }}<br>
@endsection
@section('eofScript')
    <script>
        function Buy(id) {

            var amount = prompt("請輸入購買數量!", "1");
            if (isNaN(amount) || amount < 0 || !amount) {
                alert("請輸入正確數字");
                return;
            }
            $.post("buy",
                {
                    id: id,
                    amount: amount,
                },
                function (data) {
                    location.reload();
                });
        }
    </script>
@endsection
@include('base')