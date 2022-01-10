@extends('shop::customers.account.index')

@section('page_title')
    {{ __('bulkaddtocart::app.products.bulk-add-to-cart') }}
@endsection

@section('page-detail-wrapper')
    <div class="account-content">
        <div class="account-layout">

            <div class="account-head mb-15">
                <span class="account-heading">
                    {{ __('bulkaddtocart::app.products.bulk-add-to-cart') }}
                </span>
            </div>

            <div class="account-items-list">
                <form method="POST" action="{{ route('cart.bulk-add-to-cart.store') }}" enctype="multipart/form-data" @submit.prevent="onSubmit">
                    @csrf()

                    <div class="control-group" :class="[errors.has('file') ? 'has-error' : '']">
                        <label for="file" class="required">
                            {{ __('bulkaddtocart::app.products.file') }}
                        </label>

                        <input
                            id="file"
                            type="file"
                            name="file"
                            class="control"
                            v-validate="'required'"
                            style="padding-top: 5px" 
                            value="{{ old('file') }}"
                            data-vv-as="&quot;{{ __('bulkaddtocart::app.products.file') }}&quot;" />

                        <span>
                            {{ __('bulkaddtocart::app.products.allowed-type') }}
                        </span>

                        <span><b>
                            {{ __('bulkaddtocart::app.products.file-type') }}
                        </b></span>

                        <div class="control-error" v-if="errors.has('file')">
                            @{{ errors.first('file') }}
                        </div>

                        <div class="download-sample" style="margin-top: 10px;">
                            <span style="border-bottom: 1px solid red;">
                                <a href="{{ route('cart.bulk-add-to-cart.sample.download') }}" style="color: red;">
                                    {{ __('bulkaddtocart::app.products.download-sample') }}
                                </a>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="theme-btn mt-4">
                        {{ __('bulkaddtocart::app.products.submit') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection