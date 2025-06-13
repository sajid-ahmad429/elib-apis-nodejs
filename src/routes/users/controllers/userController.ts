import { Request, Response } from "express";
import { validationResult } from "express-validator";
import { config } from "../../../config/config";
import User from "../models/userModel";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";

export const createUsers = async (req: Request, res: Response) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        res.status(422).json({
            status: 422,
            message: "Validation failed",
            errors: errors.array(),
        });
    }

    const { firstName, lastName, userName, email, password, role, status } = req.body;

    if (!password) {
        res.status(400).json({
            status: 400,
            message: "Password is required",
        });
    }

    try {
        const existingUser = await User.findOne({ email });
        if (existingUser) {
            res.status(400).json({
                status: 400,
                message: "User already exists",
            });
        }

        const hashedPassword = await bcrypt.hash(password, 10);

        const newUser = new User({
            firstName,
            lastName,
            userName,
            email,
            password: hashedPassword,
            role,
            status,
        });

        await newUser.save();

        // Generate JWT Token
        const payload = {
            userId: newUser._id,
            email: newUser.email,
            role: newUser.role,
        };

        const token = jwt.sign(
            payload,
            config.jwt.secret || "default_secure_secret", // replace with env var in production
            { expiresIn: "7d" } // Token expires in 1 hour
        );

        res.status(201).json({
            status: 201,
            message: "User created successfully",
            data: {
                id: newUser._id,
                firstName: newUser.firstName,
                lastName: newUser.lastName,
                userName: newUser.userName,
                email: newUser.email,
                role: newUser.role,
                status: newUser.status,
            },
            token,
        });
    } catch (error) {
        console.error("Error creating user:", error);
        res.status(500).json({
            status: 500,
            message: "Server error",
        });
    }
};
