package com.spring.productservice.controller;

import com.spring.productservice.dto.ProductRequest;
import com.spring.productservice.dto.ProductResponse;
import com.spring.productservice.service.ProductService;
import lombok.RequiredArgsConstructor;
import org.springframework.http.HttpStatus;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/product")
@RequiredArgsConstructor
public class ProductController {

    private final ProductService productService;
    @PostMapping
    @ResponseStatus(HttpStatus.CREATED)
    public  void createProduct(@RequestBody  ProductRequest productrequest){
        productService.createProduct(productrequest);
    }

    @GetMapping
    @ResponseStatus(HttpStatus.OK)
    public List<ProductResponse> getAllProducts(){

        return productService.getAllProducts();


    }


}
